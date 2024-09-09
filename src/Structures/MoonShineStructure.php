<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Support\TypeMap;

final readonly class MoonShineStructure
{
    private TypeMap $fieldMap;

    public function __construct(
        private CodeStructure $codeStructure
    ) {
        $this->fieldMap = new TypeMap();
    }

    /**
     * @throws ProjectBuilderException
     * @return array<int, string>
     */
    public function getUsesForFields(): array
    {
        $uses = [];

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $column->dataValue('field_class')
                ? $this->fieldMap->fieldClassFromAlias($column->dataValue('field_class'))
                : $this->fieldMap->getMoonShineFieldFromSqlType($column->type())
            ;

            $use = str($fieldClass)
                ->prepend('use ')
                ->append(';')
                ->value()
            ;

            if(in_array($use, $uses)) {
                continue;
            }

            $uses[] = $use;
        }

        return $uses;
    }

    /**
     * @throws ProjectBuilderException
     * @return array<int, string>
     */
    public function getFields(int $tabulation = 0): array
    {
        $fields = [];

        foreach ($this->codeStructure->columns() as $column) {
            if($column->isLaravelTimestamp()) {
                continue;
            }

            $fieldClass = $column->dataValue('field_class')
                ? $this->fieldMap->fieldClassFromAlias($column->dataValue('field_class'))
                : $this->fieldMap->getMoonShineFieldFromSqlType($column->type())
            ;

            //dump($fieldClass);

            if(! is_null($column->relation())) {
                $resourceName = str($column->relation()->table()->camel())->singular()->ucfirst()->value();

                $relationMethod = $column->relation()->table();
                $relationMethod = $column->type() === SqlTypeMap::BELONGS_TO
                    ? $relationMethod->singular()
                    : $relationMethod->plural();

                $fields[] = str(class_basename($fieldClass))
                    ->append('::make')
                    ->append("('{$column->name()}', '$relationMethod'")
                    ->append(", resource: new ")
                    ->when(
                        $column->dataValue('resource_class'),
                        fn ($str) => $str->append($column->dataValue('resource_class')),
                        fn ($str) => $str->append(str($resourceName)->append('Resource')->value()),
                    )
                    ->append('())')
                    ->append($this->resourceMethods($column, $tabulation))
                    ->value();

                continue;
            }

            $fields[] = str(class_basename($fieldClass))
                ->append('::make')
                ->when(
                    ! $column->isId(),
                    fn ($str) => $str->append("('{$column->name()}', '{$column->column()}')"),
                    fn ($str) => $str->append("('{$column->column()}')"),
                )
                ->append($this->resourceMethods($column, $tabulation))
                ->value()
            ;
        }

        return $fields;
    }

    /**
     * @return array<int, string>
     */
    public function getRules(): array
    {
        $rules = [];

        foreach ($this->codeStructure->columns() as $column) {
            if(
                in_array($column->column(), $this->codeStructure->dateColumns())
                || in_array($column->type(), $this->codeStructure->noInputType())
            ) {
                continue;
            }

            $rules[] = str("'{$column->column()}' => ['{$column->rulesType()}'")
                ->when(
                    $column->type() === SqlTypeMap::BOOLEAN,
                    fn ($str) => $str->append(", 'sometimes'"),
                    fn ($str) => $str->append(", 'nullable'")
                )
                ->append(']')
                ->value()
            ;
        }

        return $rules;
    }

    /**
     * @return array<int, string>
     */
    public function getWithProperty(): array
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

            $withArray[] = $relationMethod;
        }

        return $withArray;
    }

    private function resourceMethods(ColumnStructure $columnStructure, int $tabulation = 0): string
    {
        if(
            empty($columnStructure->dataValue('resource_methods'))
            || ! is_array($columnStructure->dataValue('resource_methods'))
        ) {
            return '';
        }

        $tabStr = $tabulation ? str_repeat("\t", $tabulation) : '';

        $result = "";

        foreach ($columnStructure->dataValue('resource_methods') as $method) {
            if(! str_contains($method, '(')) {
                $method .= "()";
            }
            $result .= str('')
                    ->when($tabulation > 0,
                        fn($str) => $str->newLine()->append($tabStr)
                    )
                    ->value() . "->$method";
        }

        return $result;
    }
}