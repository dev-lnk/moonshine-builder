<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\Builders\Core\Contracts\EditActionBuilderContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Support\TypeMap;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourceBuilder extends AbstractBuilder implements EditActionBuilderContract
{
    /**
     * @throws FileNotFoundException
     * @throws ProjectBuilderException
     */
    public function build(): void
    {
        $resourcePath = $this->codePath->path(MoonShineBuildType::RESOURCE->value);
        $modelPath = $this->codePath->path(MoonShineBuildType::MODEL->value);

        $modelUse = class_exists($modelPath->namespace() . '\\' . $modelPath->rawName())
            ? "\nuse {$modelPath->namespace()}\\{$modelPath->rawName()};"
            : "";

        $withArray = $this->withArray();

        StubBuilder::make($this->stubFile)
            ->setKey(
                '{column}',
                str('')
                ->newLine()
                ->newLine()
                ->append("\t")
                ->append("protected string \$column = '{$this->codeStructure->dataValue('column')}';")
                ->value(),
                ! is_null($this->codeStructure->dataValue('column'))
            )
            ->setKey(
                '{model_use}',
                $modelUse,
                ! empty($modelUse)
            )
            ->setKey(
                '{todo_model_not_found}',
                "// TODO model not found\n\t",
                empty($modelUse)
            )
            ->setKey(
                '{with_array}',
                "\n\n\tprotected array \$with = [{with}];",
                ! empty($withArray)
            )
            ->makeFromStub($resourcePath->file(), [
                '{namespace}' => $resourcePath->namespace(),
                '{field_uses}' => $this->usesFieldsToResource(),
                '{class}' => $resourcePath->rawName(),
                '{model}' => $modelPath->rawName(),
                '{with}' => $this->withArray(),
                '{fields}' => $this->columnsToResource(),
                '{rules}' => $this->columnsToRules(),
            ]);
    }

    /**
     * @throws ProjectBuilderException
     */
    protected function usesFieldsToResource(): string
    {
        $fieldMap = new TypeMap();

        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $column->dataValue('field_class')
                ? $fieldMap->fieldClassFromAlias($column->dataValue('field_class'))
                : $fieldMap->getMoonShineFieldFromSqlType($column->type())
            ;

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

    /**
     * @throws ProjectBuilderException
     */
    protected function columnsToResource(): string
    {
        $fieldMap = new TypeMap();

        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $column->dataValue('field_class')
                ? $fieldMap->fieldClassFromAlias($column->dataValue('field_class'))
                : $fieldMap->getMoonShineFieldFromSqlType($column->type())
            ;

            if(! is_null($column->relation())) {
                $resourceName = str($column->relation()->table()->camel())->singular()->ucfirst()->value();

                $relationMethod = $column->relation()->table();
                $relationMethod = $column->type() === SqlTypeMap::BELONGS_TO
                    ? $relationMethod->singular()
                    : $relationMethod->plural();

                $result .= str(class_basename($fieldClass))
                    ->prepend("\t\t\t\t")
                    ->prepend("\n")
                    ->append('::make')
                    ->append("('{$column->name()}', '$relationMethod'")
                    ->append(", resource: new ")
                    ->when(
                        $column->dataValue('resource_class'),
                        fn ($str) => $str->append($column->dataValue('resource_class')),
                        fn ($str) => $str->append(str($resourceName)->append('Resource')->value()),
                    )
                    ->append('())')
                    ->append($this->resourceMethods($column))
                    ->append(',')
                    ->value();

                continue;
            }

            $result .= str(class_basename($fieldClass))
                ->prepend("\t\t\t\t")
                ->prepend("\n")
                ->append('::make')
                ->when(
                    ! $column->isId(),
                    fn ($str) => $str->append("('{$column->name()}', '{$column->column()}')"),
                    fn ($str) => $str->append("('{$column->column()}')"),
                )
                ->append($this->resourceMethods($column))
                ->append(',')
                ->value()
            ;
        }

        return $result;
    }

    public function resourceMethods(ColumnStructure $columnStructure): string
    {
        if(
            empty($columnStructure->dataValue('resource_methods'))
            || ! is_array($columnStructure->dataValue('resource_methods'))
        ) {
            return '';
        }

        $result = "";

        foreach ($columnStructure->dataValue('resource_methods') as $method) {
            if(! str_contains($method, '(')) {
                $method .= "()";
            }
            $result .= str('')->newLine()->append("\t\t\t\t\t")->value() . "->$method";
        }

        return $result;
    }

    public function columnsToRules(): string
    {
        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if(
                in_array($column->column(), $this->codeStructure->dateColumns())
                || in_array($column->type(), $this->codeStructure->noInputType())
            ) {
                continue;
            }

            $result .= str("'{$column->column()}' => ['{$column->rulesType()}'")
                ->when(
                    $column->type() === SqlTypeMap::BOOLEAN,
                    fn ($str) => $str->append(", 'sometimes'"),
                    fn ($str) => $str->append(", 'nullable'")
                )
                ->append(']')
                ->prepend("\t\t\t")
                ->prepend(PHP_EOL)
                ->append(',')
                ->value()
            ;
        }

        return $result;
    }

    public function withArray(): string
    {
        $withArray = [];
        foreach ($this->codeStructure->columns() as $column) {
            if(! $column->relation()) {
                continue;
            }

            $relationMethod = $column->relation()->table();
            $relationMethod = $column->type() === SqlTypeMap::BELONGS_TO
                ? $relationMethod->singular()
                : $relationMethod->plural();

            $withArray[] = "'$relationMethod'";
        }

        return implode(',', $withArray);
    }
}
