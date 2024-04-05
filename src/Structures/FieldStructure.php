<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

final class FieldStructure
{
    private string $type = '';

    private array $migrationOptions = [];

    private array $migrationMethods = [];

    public function __construct(
        private string $name
    ) {
    }

    public function setType(string $type): self
    {
        if(str_contains($type, '(')) {
            $optionsStr = str($type)->match('/\((.*?)\)/')->value();

            $this->migrationOptions = array_map('trim', explode(',', $optionsStr));

            $type = str($type)->replace("($optionsStr)", '')->value();
        }

        $this->type = $type;

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

    public function migrationName(): string
    {
        return str($this->type)
            ->when($this->name === 'id' && $this->type === 'id',
                fn($str) => $str->append("("),
                fn($str) => $str->append("('{$this->name}'")
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
            $result .= "->$method";
        }

        return $result;
    }
}