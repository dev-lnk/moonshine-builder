<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\RelationStructure;
use DevLnk\MoonShineBuilder\Structures\FieldStructure;
use DevLnk\MoonShineBuilder\Structures\CodeStructureList;
use DevLnk\MoonShineBuilder\Structures\RelationFieldStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Support\TypeMap;
use DevLnk\MoonShineBuilder\Traits\Makeable;

final class StructureFromJson implements MakeStructureContract
{
    use Makeable;

    public function __construct(
        private readonly string $filePath
    ) {
    }

    /**
     * @throws ProjectBuilderException
     */
    public function makeStructures(): CodeStructureList
    {
        if(! file_exists($this->filePath)) {
            throw new ProjectBuilderException('File not available: ' .  $this->filePath);
        }

        $file = json_decode(file_get_contents($this->filePath), true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new ProjectBuilderException('Wrong json data');
        }

        $codeStructures = new CodeStructureList();

        if( isset($file['withModel'])) {
            $codeStructures->setWithModel($file['withModel']);
        }

        if( isset($file['withMigration'])) {
            $codeStructures->setWithMigration($file['withMigration']);
        }

        if( isset($file['withResource'])) {
            $codeStructures->setWithResource($file['withResource']);
        }

        foreach ($file['resources'] as $resource) {
            foreach ($resource as $name => $values) {
                $codeStructure = new CodeStructure($name, $name);

                $codeStructure->setDataValue('column', $values['column'] ?? '');

                foreach ($values['fields'] as $fieldColumn => $field) {

                    $type = SqlTypeMap::from($field['type']);

                    $columnStructure = new ColumnStructure(
                        column: $fieldColumn,
                        name: $field['name'] ?? '',
                        type: $type,
                        default: isset($field['default']) ? (string) $field['default'] : null,
                        nullable: true
                    );

                    if(! empty($field['relation'])) {
                        $relationId = (
                            $columnStructure->type() === SqlTypeMap::BELONGS_TO
                            || $columnStructure->type() === SqlTypeMap::BELONGS_TO_MANY
                        ) ? 'id'
                        : str($name)->singular()->lower()->append('_id')->value();

                        $columnStructure->setRelation(new RelationStructure(
                            $relationId,
                            $field['relation']
                        ));
                    }

                    $columnStructure->setDataValue('field', $field['field'] ?? '');

                    if(isset($field['default'])) {
                        if(! isset($field['methods'])) {
                            $field['methods'][] = "default({$field['default']})";
                        } else {
                            array_unshift($field['methods'], "default({$field['default']})");
                        }

                        if(! isset($field['migration']['methods'])) {
                            $field['migration']['methods'][] = "default({$field['default']})";
                        } else {
                            array_unshift($field['migration']['methods'], "default({$field['default']})");
                        }
                    }

                    if(! empty($field['methods'])) {
                        $columnStructure->setDataValue('resource_methods', $field['methods']);
                    }

                    if(! empty($field['migration'])) {
                        if(! empty($field['migration']['options'])) {
                            $columnStructure->setDataValue('migration_options', $field['migration']['options']);
                        }

                        if(! empty($field['migration']['methods'])) {
                            $columnStructure->setDataValue('migration_methods', $field['migration']['methods']);
                        }
                    }

                    if(! empty($field['resource_class'])) {
                        $columnStructure->setDataValue('resource_class', $field['resource_class']);
                    }

                    if(! empty($field['model_class'])) {
                        $columnStructure->setDataValue('model_class', $field['model_class']);
                    }

                    $codeStructure->addColumn($columnStructure);
                }

                if(isset($values['timestamps']) && $values['timestamps'] === true) {
                    $createdAtField = new ColumnStructure(
                        column: 'created_at',
                        name: 'created_at',
                        type: SqlTypeMap::TIMESTAMP,
                        default: null,
                        nullable: true
                    );
                    $codeStructure->addColumn($createdAtField);

                    $updatedAtField = new ColumnStructure(
                        column: 'updated_at',
                        name: 'updated_at',
                        type: SqlTypeMap::TIMESTAMP,
                        default: null,
                        nullable: true
                    );
                    $codeStructure->addColumn($updatedAtField);
                }

                if(isset($values['soft_deletes']) && $values['soft_deletes'] === true) {
                    $softDeletes = new ColumnStructure(
                        column: 'deleted_at',
                        name: 'deleted_at',
                        type: SqlTypeMap::TIMESTAMP,
                        default: null,
                        nullable: true
                    );
                    $codeStructure->addColumn($softDeletes);
                }

                $codeStructures->addCodeStructure($codeStructure);
            }
        }

        return $codeStructures;
    }

    private function getFieldBuilder(string $fieldColumn, array $field): FieldStructure
    {
        if( empty($field['relation'])) {
            return new FieldStructure($fieldColumn, $field['name'] ?? '');
        }

        $fieldStructure = new RelationFieldStructure($field['relation'], $fieldColumn, $field['name'] ?? '');

        return $fieldStructure
            ->setForeignId($field['foreign_id'] ?? '')
            ->setModelClass($field['model_class'] ?? '')
            ->setResourceClass($field['resource_class'] ?? '')
        ;
    }
}