<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures\Factories;

use MoonShine\ProjectBuilder\Structures\FieldStructure;
use MoonShine\ProjectBuilder\Structures\MainStructure;
use MoonShine\ProjectBuilder\Structures\RelationFieldStructure;
use MoonShine\ProjectBuilder\Structures\ResourceStructure;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;
use MoonShine\ProjectBuilder\Traits\Makeable;

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

        $mainBuilder = new MainStructure();

        foreach ($file['resources'] as $resource) {
            foreach ($resource as $name => $values) {
                $resourceBuilder = new ResourceStructure($name);

                $resourceBuilder->setColumn($values['column'] ?? '');

                foreach ($values['fields'] as $fieldColumn => $field) {
                    $fieldBuilder = $this->getFieldBuilder($fieldColumn, $field);

                    $fieldBuilder->setType($field['type']);

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

                $mainBuilder->addResource($resourceBuilder);
            }
        }

        return $mainBuilder;
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