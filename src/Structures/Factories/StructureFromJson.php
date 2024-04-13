<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Structures\FieldStructure;
use DevLnk\MoonShineBuilder\Structures\MainStructure;
use DevLnk\MoonShineBuilder\Structures\RelationFieldStructure;
use DevLnk\MoonShineBuilder\Structures\ResourceStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
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
    public function makeStructure(): MainStructure
    {
        if(! file_exists($this->filePath)) {
            throw new ProjectBuilderException('File not available: ' .  $this->filePath);
        }

        $file = json_decode(file_get_contents($this->filePath), true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new ProjectBuilderException('Wrong json data');
        }

        $mainStructure = new MainStructure();

        if( isset($file['withModel'])) {
            $mainStructure->setWithModel($file['withModel']);
        }

        if( isset($file['withMigration'])) {
            $mainStructure->setWithMigration($file['withMigration']);
        }

        if( isset($file['withResource'])) {
            $mainStructure->setWithResource($file['withResource']);
        }

        foreach ($file['resources'] as $resource) {
            foreach ($resource as $name => $values) {
                $resourceBuilder = new ResourceStructure($name);

                $resourceBuilder->setColumn($values['column'] ?? '');

                foreach ($values['fields'] as $fieldColumn => $field) {
                    $fieldBuilder = $this->getFieldBuilder($fieldColumn, $field);

                    $fieldBuilder
                        ->setType($field['type'])
                        ->setField($field['field'] ?? '')
                    ;

                    if(! empty($field['methods'])) {
                        $fieldBuilder->addResourceMethods($field['methods']);
                    }

                    if(! empty($field['migration'])) {
                        if(! empty($field['migration']['option'])) {
                            $fieldBuilder->addMigrationOptions($field['migration']['option']);
                        }

                        if(! empty($field['migration']['methods'])) {
                            $fieldBuilder->addMigrationMethod($field['migration']['methods']);
                        }
                    }

                    $resourceBuilder->addField($fieldBuilder);
                }

                $mainStructure->addResource($resourceBuilder);
            }
        }

        return $mainStructure;
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