<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Stringable;
use DevLnk\MoonShineBuilder\Support\NameStr;

class RelationFieldStructure extends FieldStructure
{
    private NameStr $relation;

    private string $foreignId = '';

    private string $modelClass = '';

    private string $resourceClass = '';

    public function __construct(
        string $relation,
        string $column,
        string $name = ''
    )
    {
        parent::__construct($column, $name);

        $this->relation = new NameStr($relation);
    }

    public function setForeignId(?string $foreignId): self
    {
        $this->foreignId = $foreignId;

        return $this;
    }

    public function setModelClass(string $modelClass): self
    {
        $this->modelClass = $modelClass;

        return $this;
    }

    public function setResourceClass(string $resourceClass): self
    {
        $this->resourceClass = $resourceClass;

        return $this;
    }

    public function relation(): ?NameStr
    {
        return $this->relation;
    }

    public function resourceClass(): string
    {
        return $this->resourceClass;
    }

    public function getModelUse(): string
    {
        return match ($this->type()) {
            'BelongsTo' => BelongsTo::class,
            'HasMany' => HasMany::class,
            'HasOne' => HasOne::class,
            default => ''
        };
    }

    /**
     * @return array<string, string>
     */
    public function relationData(): array
    {
        $stub = str($this->type())
            ->ucfirst()
            ->value();

        $modelName = $this->isManyField()
            ? $this->relation()->ucFirstSingular()
            : $this->relation()->ucFirst() ;

        $relationModel = ! empty($this->modelClass)
            ? $this->modelClass . '::class'
            : $modelName . '::class'
        ;

        return [
            'stub' => $stub,
            'relation' => $this->relation()->camel(),
            'relation_model' => $relationModel
        ];
    }

    public function migrationName(): string
    {
        if($this->getModelUse() !== BelongsTo::class) {
            return '';
        }

        $modelClass = empty($this->modelClass) ? '\\App\\Models\\' : $this->modelClass;

        return str('foreignIdFor')
            ->append('(')
            ->append($modelClass)
            ->when(empty($this->modelClass),
                fn($str) => $str->append($this->relation()->ucFirst())
            )
            ->append("::class")
            ->when($this->foreignId,
                fn($str) => $str->append(", '{$this->foreignId}'")
            )
            ->append(')')
            ->when(true, fn($str) => newLineWithTab($str))
            ->append('->constrained()')
            ->when(true, fn($str) => newLineWithTab($str))
            ->append('->cascadeOnDelete()')
            ->when(true, fn($str) => newLineWithTab($str))
            ->append('->cascadeOnUpdate()')
            ->value()
        ;
    }
}