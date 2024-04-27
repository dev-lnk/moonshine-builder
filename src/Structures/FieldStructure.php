<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use DevLnk\MoonShineBuilder\Enums\LaravelSqlType;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Support\TypeMap;

class FieldStructure
{
    /**
     * Field type, for example int
     *
     * @var string
     */
    private string $type = '';

    /**
     * Class fields to generate in a resource
     *
     * @see FieldStructure::setField()
     * @see FieldStructure::setFieldClass()
     *
     * @var string|null
     */
    private ?string $fieldClass = null;

    /**
     * Methods for a field in resources
     *
     * @var array<int, string>
     */
    private array $resourceMethods = [];

    /**
     * Options for creating a field in a table, for example string('name', [options])
     *
     * @var array
     */
    private array $migrationOptions = [];

    /**
     * Methods for a field in migrations
     *
     * @var array<int, string>
     */
    private array $migrationMethods = [];

    private TypeMap $typeMap;

    public function __construct(
        private readonly string $column,
        private string $name = '',
    ) {
        $this->typeMap = new TypeMap();

        if(empty($this->name)) {
            $this->name = str($this->column)->camel()->ucfirst()->value();
        }
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

    public function isCreatedAt(): bool
    {
        return $this->column() === 'created_at';
    }

    public function isUpdatedAt(): bool
    {
        return $this->column() === 'updated_at';
    }

    public function isDeletedAt(): bool
    {
        return $this->column() === 'deleted_at';
    }

    public function isLaravelTimestamp(): bool
    {
        return $this->isCreatedAt() || $this->isUpdatedAt() || $this->isDeletedAt();
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

        $this->fieldClass = $this->typeMap->fieldClassFromAlias($field);

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

        $this->fieldClass = LaravelSqlType::from($this->type())->getMoonShineField();

        return $this;
    }
}