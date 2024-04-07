<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

use Closure;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Stringable;
use MoonShine\ProjectBuilder\Support\NameStr;

class RelationFieldStructure extends FieldStructure
{
    private NameStr $relation;

    private string $relationKey = '';

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

    public function setRelationKey(string $relationKey): self
    {
        $this->relationKey = $relationKey;

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
            "belongsTo" => BelongsTo::class,
            default => ''
        };
    }

    /**
     * @return array<string, string>
     */
    public function relationData(): array
    {
        $stub = str($this->type())->ucfirst()
            ->when(! empty($this->relationKey),
                fn($str) => $str->append('WithKey')
            )
            ->value();

        $relationModel = ! empty($this->modelClass)
            ? $this->modelClass . '::class'
            : $this->relation()->ucFirst() . '::class'
        ;

        return [
            'stub' => $stub,
            'relation' => $this->relation()->camel(),
            'relation_model' => $relationModel,
            'relation_key' => $this->relationKey ?? '',
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
            //TODO foreign key
//            ->when($this->relationKey,
//                fn($str) => $str->append(", '{$this->relationKey}'")
//            )
            ->append(')')
            ->tap(fn($str) => $this->foreignStrFunction($str))
            ->append('->constrained()')
            ->tap(fn($str) => $this->foreignStrFunction($str))
            ->append('->cascadeOnDelete()')
            ->tap(fn($str) => $this->foreignStrFunction($str))
            ->append('->cascadeOnUpdate()')
            ->value()
        ;
    }

    private function foreignStrFunction(Stringable $str): Stringable
    {
        return $str->newLine()
            ->append('    ')
            ->append('    ')
            ->append('    ')
            ->append('    ');
    }
}