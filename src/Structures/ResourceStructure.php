<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

final class ResourceStructure
{
    /**
     * @var FieldStructure[]
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
     * @return FieldStructure[]
     */
    public function fields(): array
    {
        return $this->fields;
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
}