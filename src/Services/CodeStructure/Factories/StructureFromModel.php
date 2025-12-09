<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\CodeStructure\Factories;

use DevLnk\MoonShineBuilder\Enums\SqlTypeMap;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructureList;
use DevLnk\MoonShineBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\MoonShineBuilder\Services\CodeStructure\RelationStructure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use ReflectionClass;
use ReflectionMethod;

final readonly class StructureFromModel implements MakeStructureContract
{
    public function __construct(
        private string $modelClassName
    ) {
    }

    /**
     * @throws ProjectBuilderException
     */
    public function makeStructures(): CodeStructureList
    {
        $codeStructureList = new CodeStructureList();
        $codeStructureList->addCodeStructure($this->makeStructure());

        return $codeStructureList;
    }

    /**
     * @throws ProjectBuilderException
     */
    private function makeStructure(): CodeStructure
    {
        $normalizedClassName = ltrim($this->modelClassName, '\\');
        
        if (!class_exists($normalizedClassName)) {
            throw new ProjectBuilderException("Model class '{$normalizedClassName}' not found.");
        }

        $model = resolve($normalizedClassName);

        if (!$model instanceof Model) {
            throw new ProjectBuilderException("Class '{$normalizedClassName}' is not an Eloquent Model.");
        }

        $reflection = new ReflectionClass($model);
        $table = $model->getTable();
        
        $entity = $this->getEntityFromClassName($normalizedClassName);

        $codeStructure = new CodeStructure($table, $entity);
        
        $codeStructure->setWithModel(false);
        $codeStructure->setWithMigration(false);

        $this->addPrimaryKey($codeStructure, $model);
        $this->addColumns($codeStructure, $model);
        $this->addRelationsFromModel($codeStructure, $reflection, $model);
        $this->addTimestampsIfNeeded($codeStructure, $model, $reflection);

        return $codeStructure;
    }
    
    private function getEntityFromClassName(string $className): string
    {
        $parts = explode('\\', $className);
        
        return end($parts);
    }

    private function addPrimaryKey(CodeStructure $codeStructure, Model $model): void
    {
        $keyName = $model->getKeyName();
        
        $columnStructure = new ColumnStructure(
            column: $keyName,
            name: str($keyName)->camel()->ucFirst()->value(),
            type: SqlTypeMap::ID,
            default: null,
            nullable: false,
            required: false,
        );

        $codeStructure->addColumn($columnStructure);
    }

    private function addTimestampsIfNeeded(CodeStructure $codeStructure, Model $model, ReflectionClass $reflection): void
    {
        if ($model->usesTimestamps()) {
            $createdAtColumn = $model->getCreatedAtColumn();
            if ($createdAtColumn) {
                $columnStructure = new ColumnStructure(
                    column: $createdAtColumn,
                    name: str($createdAtColumn)->replace('_', ' ')->title()->value(),
                    type: SqlTypeMap::TIMESTAMP,
                    default: null,
                    nullable: true,
                    required: false,
                );
                $codeStructure->addColumn($columnStructure);
            }

            $updatedAtColumn = $model->getUpdatedAtColumn();
            if ($updatedAtColumn) {
                $columnStructure = new ColumnStructure(
                    column: $updatedAtColumn,
                    name: str($updatedAtColumn)->replace('_', ' ')->title()->value(),
                    type: SqlTypeMap::TIMESTAMP,
                    default: null,
                    nullable: true,
                    required: false,
                );
                $codeStructure->addColumn($columnStructure);
            }
        }

        if ($this->usesSoftDeletes($reflection)) {
            $columnStructure = new ColumnStructure(
                column: 'deleted_at',
                name: 'Deleted At',
                type: SqlTypeMap::TIMESTAMP,
                default: null,
                nullable: true,
                required: false,
            );
            $codeStructure->addColumn($columnStructure);
        }
    }

    private function usesSoftDeletes(ReflectionClass $reflection): bool
    {
        $traits = $reflection->getTraitNames();
        
        return in_array(SoftDeletes::class, $traits);
    }

    private function addColumns(CodeStructure $codeStructure, Model $model): void
    {
        $fillable = $model->getFillable();
        $casts = $model->getCasts();
        $reflection = new ReflectionClass($model);
        $docProperties = $this->parseDocBlockProperties($reflection);
        
        foreach ($fillable as $columnName) {
            if (str_ends_with($columnName, '_id')) {
                continue;
            }

            $typeInfo = $this->getColumnType($columnName, $docProperties, $casts);

            $columnStructure = new ColumnStructure(
                column: $columnName,
                name: str($columnName)->camel()->ucFirst()->value(),
                type: $typeInfo['type'],
                default: null,
                nullable: $typeInfo['nullable'],
                required: !$typeInfo['nullable'],
            );

            $codeStructure->addColumn($columnStructure);
        }
    }

    /**
     * @return array<string, array{type: string, nullable: bool}>
     */
    private function parseDocBlockProperties(ReflectionClass $reflection): array
    {
        $docComment = $reflection->getDocComment();
        
        if (!$docComment) {
            return [];
        }

        $properties = [];
        $lines = explode("\n", $docComment);

        foreach ($lines as $line) {
            if (preg_match('/@property(-read|-write)?\s+([^\s]+)\s+\$(\w+)/', $line, $matches)) {
                $type = $matches[2];
                $propertyName = $matches[3];
                
                $nullable = str_contains($type, '?') || str_contains($type, 'null');
                $type = str_replace('?', '', $type);
                $type = trim(explode('|', $type)[0]);
                
                $properties[$propertyName] = [
                    'type' => $type,
                    'nullable' => $nullable,
                ];
            }
        }

        return $properties;
    }

    /**
     * @param array<string, array{type: string, nullable: bool}> $docProperties
     * @return array{type: SqlTypeMap, nullable: bool}
     */
    private function getColumnType(string $columnName, array $docProperties, array $casts): array
    {
        if (isset($docProperties[$columnName])) {
            $docType = $docProperties[$columnName]['type'];
            $nullable = $docProperties[$columnName]['nullable'];
            
            return [
                'type' => $this->mapPhpTypeToSqlType($docType),
                'nullable' => $nullable,
            ];
        }

        if (isset($casts[$columnName])) {
            return [
                'type' => $this->getColumnTypeFromCast($casts[$columnName]),
                'nullable' => false,
            ];
        }

        return [
            'type' => SqlTypeMap::STRING,
            'nullable' => false,
        ];
    }

    private function getColumnTypeFromCast(string $castType): SqlTypeMap
    {
        return match ($castType) {
            'int', 'integer' => SqlTypeMap::INTEGER,
            'real', 'float', 'double' => SqlTypeMap::FLOAT,
            'decimal' => SqlTypeMap::DECIMAL,
            'string' => SqlTypeMap::STRING,
            'bool', 'boolean' => SqlTypeMap::BOOLEAN,
            'array', 'json' => SqlTypeMap::JSON,
            'date' => SqlTypeMap::DATE,
            'datetime' => SqlTypeMap::DATE_TIME,
            'timestamp' => SqlTypeMap::TIMESTAMP,
            default => SqlTypeMap::STRING,
        };
    }

    private function mapPhpTypeToSqlType(string $phpType): SqlTypeMap
    {
        return match ($phpType) {
            'int', 'integer' => SqlTypeMap::INTEGER,
            'float', 'double' => SqlTypeMap::FLOAT,
            'string' => SqlTypeMap::STRING,
            'bool', 'boolean' => SqlTypeMap::BOOLEAN,
            'array' => SqlTypeMap::JSON,
            'Carbon', '\Carbon\Carbon', '\Illuminate\Support\Carbon' => SqlTypeMap::DATE_TIME,
            default => SqlTypeMap::STRING,
        };
    }

    private function addRelationsFromModel(CodeStructure $codeStructure, ReflectionClass $reflection, Model $model): void
    {
        $methods = $reflection->getMethods(ReflectionMethod::IS_PUBLIC);

        foreach ($methods as $method) {
            if ($method->class !== $reflection->getName()) {
                continue;
            }

            if ($method->getNumberOfParameters() > 0) {
                continue;
            }

            $returnType = $method->getReturnType();
            if (!$returnType instanceof \ReflectionNamedType) {
                continue;
            }

            $returnTypeName = $returnType->getName();

            if (!in_array($returnTypeName, [
                BelongsTo::class,
                HasMany::class,
                HasOne::class,
                BelongsToMany::class,
            ])) {
                continue;
            }

            $relationName = $method->getName();
            $relation = $method->invoke($model);

            $this->addRelationColumn($codeStructure, $relationName, $relation, $returnTypeName);
        }
    }

    private function addRelationColumn(CodeStructure $codeStructure, string $relationName, mixed $relation, string $relationType): void
    {
        $relatedModel = $relation->getRelated();
        $relatedTable = $relatedModel->getTable();

        $type = match ($relationType) {
            BelongsTo::class => SqlTypeMap::BELONGS_TO,
            HasMany::class => SqlTypeMap::HAS_MANY,
            HasOne::class => SqlTypeMap::HAS_ONE,
            BelongsToMany::class => SqlTypeMap::BELONGS_TO_MANY,
            default => null,
        };

        if (!$type) {
            return;
        }

        $foreignColumn = match ($relationType) {
            BelongsTo::class => $relation->getOwnerKeyName(),
            HasMany::class, HasOne::class => $relation->getForeignKeyName(),
            BelongsToMany::class => $relation->getRelatedKeyName(),
            default => 'id',
        };

        if ($relationType === BelongsTo::class) {
            $foreignKeyName = $relation->getForeignKeyName();

            $columnStructure = new ColumnStructure(
                column: $foreignKeyName,
                name: str($relationName)->camel()->ucFirst()->value(),
                type: $type,
                default: null,
                nullable: false,
                required: true,
            );

            $columnStructure->setRelation(new RelationStructure(
                foreignColumn: $foreignColumn,
                modelRelationName: $relationName,
                table: $relatedTable
            ));

            $codeStructure->addColumn($columnStructure);
            
            return;
        }

        $columnName = $relationType === BelongsToMany::class || $relationType === HasMany::class
            ? $relatedTable
            : str($relatedTable)->singular()->value();

        $default = $relationType === BelongsToMany::class || $relationType === HasMany::class ? '[]' : null;

        $columnStructure = new ColumnStructure(
            column: $columnName,
            name: str($relationName)->camel()->ucFirst()->value(),
            type: $type,
            default: $default,
            nullable: false,
            required: false,
        );

        $columnStructure->setRelation(new RelationStructure(
            foreignColumn: $foreignColumn,
            modelRelationName: $relationName,
            table: $relatedTable
        ));

        $codeStructure->addColumn($columnStructure);
    }
}