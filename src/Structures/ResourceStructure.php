<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

use MoonShine\Fields\ID;
use MoonShine\ProjectBuilder\Support\NameStr;

final class ResourceStructure
{
    /**
     * @var array<int, FieldStructure>
     */
    private array $fields = [];

    /**
     * Main name for generation
     *
     * @var NameStr
     */
    private NameStr $name;

    /**
     * $column property of a Resource
     *
     * @var string
     */
    private string $column = '';

    public function __construct(
        string $name
    ) {
        $this->name = new NameStr(str($name)->replace('Resource', '')->value());
    }

    public function addField(FieldStructure $fieldBuilder): self
    {
        $this->fields[] = $fieldBuilder;

        return $this;
    }

    /**
     * @return array<int, FieldStructure>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    public function column(): string
    {
        return $this->column;
    }

    public function setColumn(string $column): self
    {
        $this->column = $column;

        return $this;
    }

    /**
     * @return array<int, RelationFieldStructure>
     */
    public function relationFields(): array
    {
        return array_filter($this->fields, fn($fieldStructure) => $fieldStructure instanceof RelationFieldStructure);
    }

    public function name(): NameStr
    {
        return $this->name;
    }

    public function resourceName(): string
    {
        return $this->name->raw().'Resource';
    }

    public function columnToResource(): string
    {
        if(empty($this->column)) {
            return '';
        }

        return "protected string \$column = '{$this->column}';".PHP_EOL;
    }

    public function fieldsToModel(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            if($field->type() === 'id') {
                continue;
            }
            $result .= str("'{$field->column()}'")
                ->append(',')
                ->newLine()
                ->append('    ')
                ->append('    ')
                ->value()
            ;
        }

        return $result;
    }

    public function fieldsToMigration(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            if($field->isHasField()) {
                continue;
            }

            $result .= str('$table->')
                ->append($field->migrationName())
                ->append($field->migrationMethods())
                ->append(';')
                ->newLine()
                ->append('    ')
                ->append('    ')
                ->append('    ')
                ->value()
            ;
        }

        return $result;
    }

    public function usesFieldsToResource(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            $fieldClass = $field->fieldClass();

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

    public function fieldsToResources(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            if($field instanceof RelationFieldStructure) {
                $resourceName = $field->isManyField()
                    ? $field->relation()->ucFirstSingular()
                    : $field->relation()->ucFirst() ;

                $result .= str(class_basename($field->fieldClass()))
                    ->append('::make')
                    ->append("('{$field->name()}', '{$field->relation()->raw()}'")
                    ->append(", resource: new ")
                    ->when($field->resourceClass(),
                        fn($str) => $str->append($field->resourceClass()),
                        fn($str) => $str->append(str($resourceName)->append('Resource')->value()),
                    )
                    ->append('())')
                    ->append($field->resourceMethods())
                    ->append(',')
                    ->when(true, fn($str) => newLineWithTab($str))
                    ->value();

                continue;
            }

            $result .= str(class_basename($field->fieldClass()))
                ->append('::make')
                ->when($field->fieldClass() !== ID::class,
                    fn($str) => $str->append("('{$field->name()}', '{$field->column()}')"),
                    fn($str) => $str->append("('{$field->column()}')"),
                )
                ->append($field->resourceMethods())
                ->append(',')
                ->when(true, fn($str) => newLineWithTab($str))
                ->value()
            ;
        }

        return $result;
    }

    public function relationUses(): string
    {
        $result = "";

        foreach ($this->relationFields() as $relationField) {
            if(str_contains($result, $relationField->getModelUse())) {
                continue;
            }

            $result .= str($relationField->getModelUse())
                ->prepend("use ")
                ->append(";")
                ->newLine()
                ->value();
        }

        return $result;
    }

    /**
     * @return array<int, array<string, string>>
     */
    public function relationsData(): array
    {
        return array_map(fn(RelationFieldStructure $fieldStructure)
            => $fieldStructure->relationData(),
            $this->relationFields()
        );
    }
}