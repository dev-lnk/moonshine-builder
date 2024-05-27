<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\RelationStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\CodeStructureList;
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
            throw new ProjectBuilderException('File not available: ' . $this->filePath);
        }

        $file = json_decode(file_get_contents($this->filePath), true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new ProjectBuilderException('Wrong json data');
        }

        $codeStructures = new CodeStructureList();

        if(isset($file['withModel'])) {
            $codeStructures->setWithModel($file['withModel']);
        }

        if(isset($file['withMigration'])) {
            $codeStructures->setWithMigration($file['withMigration']);
        }

        if(isset($file['withResource'])) {
            $codeStructures->setWithResource($file['withResource']);
        }

        foreach ($file['resources'] as $resource) {
            foreach ($resource as $name => $values) {
                $codeStructure = new CodeStructure(str($name)->snake()->lower()->plural()->value(), $name);

                $codeStructure->setDataValue('column', $values['column'] ?? null);

                foreach ($values['fields'] as $fieldColumn => $field) {

                    $columnStructure = new ColumnStructure(
                        column: $fieldColumn,
                        name: $field['name'] ?? '',
                        type: SqlTypeMap::from($field['type']),
                        default: isset($field['default']) ? (string) $field['default'] : null,
                        nullable: true
                    );

                    if(! empty($field['relation'])) {
                        if(
                            ! isset($field['relation']['foreign_key'])
                             && (
                                 $columnStructure->type() === SqlTypeMap::BELONGS_TO
                                || $columnStructure->type() === SqlTypeMap::BELONGS_TO_MANY
                             )
                        ) {
                            $field['relation']['foreign_key'] = 'id';
                        }

                        $columnStructure->setRelation(new RelationStructure(
                            $field['relation']['foreign_key'],
                            $field['relation']['table'],
                        ));
                    }

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

                    if(! empty($field['field'])) {
                        $columnStructure->setDataValue('field_class', $field['field']);
                    }

                    $codeStructure->addColumn($columnStructure);
                }

                if(isset($values['timestamps']) && $values['timestamps'] === true) {
                    $createdAtField = new ColumnStructure(
                        column: 'created_at',
                        name: 'Created at',
                        type: SqlTypeMap::TIMESTAMP,
                        default: null,
                        nullable: true
                    );
                    $codeStructure->addColumn($createdAtField);

                    $updatedAtField = new ColumnStructure(
                        column: 'updated_at',
                        name: 'Updated at',
                        type: SqlTypeMap::TIMESTAMP,
                        default: null,
                        nullable: true
                    );
                    $codeStructure->addColumn($updatedAtField);
                }

                if(isset($values['soft_deletes']) && $values['soft_deletes'] === true) {
                    $softDeletes = new ColumnStructure(
                        column: 'deleted_at',
                        name: 'Deleted at',
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
}
