<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;
use MoonShine\ProjectBuilder\Support\TypeMap;

class FieldStructure
{
    private string $type = '';

    private string $field = '';

    private ?string $fieldClass = null;

    private array $resourceMethods = [];

    private array $migrationOptions = [];

    private array $migrationMethods = [];

    private TypeMap $typeMap;

    public function __construct(
        private readonly string $column,
        private readonly string $name = '',
    ) {
        $this->typeMap = new TypeMap();
    }

    public function column(): string
    {
        return $this->column;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function type(): string
    {
        return $this->type;
    }

    public function fieldClass(): ?string
    {
        return $this->fieldClass;
    }

    public function setType(string $type): self
    {
        if(str_contains($type, '(')) {
            $optionsStr = str($type)->match('/\((.*?)\)/')->value();

            $this->migrationOptions = array_map('trim', explode(',', $optionsStr));

            $type = str($type)->replace("($optionsStr)", '')->value();
        }

        $this->type = $type;

        $this->setFieldClass();

        return $this;
    }

    /**
     * @throws ProjectBuilderException
     */
    public function setField(string $field): self
    {
        if(empty($field)) {
            return $this;
        }

        $field = str($field)->ucfirst()->value();

        $this->fieldClass = $this->typeMap->getFieldClass($field);

        return $this;
    }

    public function addResourceMethods(array $methods): self
    {
        $this->resourceMethods = $methods;
        return $this;
    }

    public function addMigrationOptions(array $options): self
    {
        $this->migrationOptions = $options;
        return $this;
    }

    public function addMigrationMethod(array $methods): self
    {
        $this->migrationMethods = $methods;
        return $this;
    }

    public function isHasField(): bool
    {
        return in_array($this->type(), [
            'HasMany',
            'HasOne',
        ]);
    }

    public function isManyField(): bool
    {
        return in_array($this->type(), [
            'HasMany',
            'BelongsToMany'
        ]);
    }

    public function migrationName(): string
    {
        return str($this->type)
            ->when($this->column === 'id' && $this->type === 'id',
                fn($str) => $str->append("("),
                fn($str) => $str->append("('{$this->column}'")
            )
            ->when(! empty($this->migrationOptions),
                fn($str) => $str->append(', ' . implode(', ', $this->migrationOptions) . ')'),
                fn($str) => $str->append(")")
            )
            ->value()
        ;
    }

    public function migrationMethods(): string
    {
        if(empty($this->migrationMethods)) {
            return '';
        }

        $result = "";

        foreach ($this->migrationMethods as $method) {
            if(! str_contains($method, '(')) {
                $method .= "()";
            }
            $result .= "->$method";

        }

        return $result;
    }

    public function resourceMethods(): string
    {
        if(empty($this->resourceMethods)) {
            return '';
        }

        $result = "";

        foreach ($this->resourceMethods as $method) {
            if(! str_contains($method, '(')) {
                $method .= "()";
            }
            $result .= newLineWithTab(str(''), 5)->value() . "->$method";
        }

        return $result;
    }

    public function setFieldClass(): self
    {
        if(! is_null($this->fieldClass)) {
            return $this;
        }

        $typeMap = $this->typeMap->getFieldFromType();

        foreach ($typeMap as $fieldClass => $findTypes) {
            if (in_array($this->type(), $findTypes, true)) {
                $this->fieldClass = $fieldClass;
            }
        }

        return $this;
    }
}