<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

final class ResourceStructure
{
    /**
     * @var array<int, FieldStructure>
     */
    private array $fields = [];

    public function __construct(
        private string $name
    ) {
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

    public function resourceName(): string
    {
        return $this->name;
    }

    public function name(): string
    {
        return str($this->name)->replace('Resource', '')->value();
    }

    public function lowName(): string
    {
        return str($this->name())->snake()->lower()->value();
    }

    public function pluralName(): string
    {
        return str($this->lowName())->plural()->value();
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

            $result .= str(class_basename($field->fieldClass()))
                ->append('::make')
                ->when($field->type() !== 'id',
                    fn($str) => $str->append("('{$field->name()}', '{$field->column()}')"),
                    fn($str) => $str->append("('{$field->column()}')"),
                )
                ->append($field->resourceMethods())
                ->append(',')
                ->newLine()
                ->append('    ')
                ->append('    ')
                ->append('    ')
                ->append('    ')
                ->value()
            ;
        }

        return $result;
    }
}