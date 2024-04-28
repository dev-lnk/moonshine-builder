<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use DevLnk\MoonShineBuilder\Enums\LaravelSqlType;
use MoonShine\Fields\ID;
use DevLnk\MoonShineBuilder\Support\NameStr;

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

    private bool $isCreatedAt = false;

    private bool $isUpdatedAt = false;

    private bool $isDeletedAt = false;

    public function __construct(
        string $name
    ) {
        $this->name = new NameStr(str($name)->replace('Resource', '')->value());
    }

    public function addField(FieldStructure $fieldBuilder): self
    {
        if(
            ($this->isCreatedAt && $fieldBuilder->isCreatedAt())
            || ($this->isUpdatedAt && $fieldBuilder->isUpdatedAt())
            || ($this->isDeletedAt && $fieldBuilder->isDeletedAt())
        ) {
            return $this;
        }

        $this->fields[] = $fieldBuilder;

        $this->setTimestamps($fieldBuilder);

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

    public function isTimestamps(): bool
    {
        return $this->isCreatedAt && $this->isUpdatedAt;
    }

    public function isSoftDeletes(): bool
    {
        return $this->isDeletedAt;
    }

    private function setTimestamps(FieldStructure $fieldBuilder): void
    {
        if(! $this->isCreatedAt && $fieldBuilder->isCreatedAt()) {
            $this->isCreatedAt = true;
            return;
        }

        if(! $this->isUpdatedAt && $fieldBuilder->isUpdatedAt()) {
            $this->isUpdatedAt = true;
            return;
        }

        if(! $this->isDeletedAt && $fieldBuilder->isDeletedAt()) {
            $this->isDeletedAt = true;
        }
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

        return "\n\n\tprotected string \$column = '{$this->column}';";
    }

    public function fieldsToModel(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            if(LaravelSqlType::from($field->type())->isIdType()) {
                continue;
            }

            $result .= str("'{$field->column()}'")
                ->prepend("\t\t")
                ->prepend("\n")
                ->append(',')
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

            if($this->isTimestamps()
                && ($field->isCreatedAt() || $field->isUpdatedAt())
            ) {
                continue;
            }

            if($this->isSoftDeletes() && $field->isDeletedAt()) {
                continue;
            }

            $result .= str('$table->')
                ->prepend("\t\t\t")
                ->prepend("\n")
                ->append($field->migrationName())
                ->append($field->migrationMethods())
                ->append(';')
                ->value()
            ;
        }

        return $result;
    }

    public function usesFieldsToResource(): string
    {
        $result = "";

        foreach ($this->fields as $field) {
            if($field->isLaravelTimestamp()) {
                continue;
            }

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
            if($field->isLaravelTimestamp()) {
                continue;
            }

            if($field instanceof RelationFieldStructure) {
                $resourceName = $field->isManyField()
                    ? $field->relation()->ucFirstSingular()
                    : $field->relation()->ucFirst() ;

                $result .= str(class_basename($field->fieldClass()))
                    ->prepend("\t\t\t\t")
                    ->prepend("\n")
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
                    ->value();

                continue;
            }

            $result .= str(class_basename($field->fieldClass()))
                ->prepend("\t\t\t\t")
                ->prepend("\n")
                ->append('::make')
                ->when($field->fieldClass() !== ID::class,
                    fn($str) => $str->append("('{$field->name()}', '{$field->column()}')"),
                    fn($str) => $str->append("('{$field->column()}')"),
                )
                ->append($field->resourceMethods())
                ->append(',')
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