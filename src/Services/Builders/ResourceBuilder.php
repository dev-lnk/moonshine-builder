<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\Builders\Core\Contracts\EditActionBuilderContract;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Support\TypeMap;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourceBuilder extends AbstractBuilder implements EditActionBuilderContract
{
    /**
     * @throws FileNotFoundException
     */
    public function build(): void
    {
        $resourcePath = $this->codePath->path(MoonShineBuildType::RESOURCE->value);
        $modelPath = $this->codePath->path(MoonShineBuildType::MODEL->value);

        $fieldUses = $this->usesFieldsToResource();

        $fields = $this->columnsToResource();

        // TODO column
        StubBuilder::make($this->stubFile)
            ->makeFromStub($resourcePath->file(), [
                '{namespace}' => $resourcePath->namespace(),
                '{model_namespace}' => $modelPath->namespace() . '\\' . $modelPath->rawName(),
                '{field_uses}' => $fieldUses,
                '{class}' => $resourcePath->rawName(),
                '{model}' => $modelPath->rawName(),
                '{column}' => '',
                '{fields}' => $fields
            ]);
    }

    protected function usesFieldsToResource(): string
    {
        $fieldMap = new TypeMap();

        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $fieldMap->getMoonShineFieldFromSqlType($column->type());

            if(str_contains($result, $fieldClass)) {
                continue;
            }

            $result .= str($fieldClass)
                ->prepend('use ')
                ->append(';')
                ->newLine()
                ->value()
            ;
        }

        return $result;
    }

    protected function columnsToResource(): string
    {
        $fieldMap = new TypeMap();

        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $fieldMap->getMoonShineFieldFromSqlType($column->type());

            if(! is_null($column->relation())) {
                $resourceName = str($column->relation()->table()->camel())->singular()->ucfirst()->value();

                $result .= str(class_basename($fieldClass))
                    ->prepend("\t\t\t\t")
                    ->prepend("\n")
                    ->append('::make')
                    ->append("('{$column->name()}', '{$column->relation()->table()->raw()}'")
                    ->append(", resource: new ")
                    ->when($column->dataValue('resource_class'),
                        fn($str) => $str->append($column->dataValue('resource_class')),
                        fn($str) => $str->append(str($resourceName)->append('Resource')->value()),
                    )
                    ->append('())')
                    //->append($field->resourceMethods())
                    ->append(',')
                    ->value();

                continue;
            }

            $result .= str(class_basename($fieldClass))
                ->prepend("\t\t\t\t")
                ->prepend("\n")
                ->append('::make')
                ->when(! $column->isId(),
                    fn($str) => $str->append("('{$column->name()}', '{$column->column()}')"),
                    fn($str) => $str->append("('{$column->column()}')"),
                )
                //->append($field->resourceMethods())
                ->append(',')
                ->value()
            ;
        }

        return $result;
    }
}