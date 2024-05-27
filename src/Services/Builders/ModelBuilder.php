<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\Builders\Core\ModelBuilder as BaseModelBuilder;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;

class ModelBuilder extends BaseModelBuilder
{
    public function relationsToModel(): string
    {
        $result = str('');

        foreach ($this->codeStructure->columns() as $column) {
            if(is_null($column->relation())) {
                continue;
            }

            $stubName = match ($column->type()) {
                SqlTypeMap::BELONGS_TO => 'BelongsTo',
                SqlTypeMap::HAS_MANY => 'HasMany',
                SqlTypeMap::HAS_ONE => 'HasOne',
                SqlTypeMap::BELONGS_TO_MANY => 'BelongsToMany',
                default => ''
            };

            if(empty($stubName)) {
                continue;
            }

            $stubBuilder = StubBuilder::make($this->codeStructure->stubDir() . $stubName);
            if($column->type() === SqlTypeMap::BELONGS_TO) {
                $stubBuilder->setKey(
                    '{relation_id}',
                    ", '{$column->relation()->foreignColumn()}'",
                    $column->relation()->foreignColumn() !== 'id'
                );
            }

            $relation = $column->relation()->table()->str();

            $relation = ($column->type() === SqlTypeMap::HAS_MANY || $column->type() === SqlTypeMap::BELONGS_TO_MANY)
                ? $relation->plural()->camel()->value()
                : $relation->singular()->camel()->value();

            $relationColumn = ($column->type() === SqlTypeMap::HAS_MANY || $column->type() === SqlTypeMap::HAS_ONE)
                ? $column->relation()->foreignColumn()
                : $column->column();

            $relationModel = ! empty($column->dataValue('model_class'))
                ? $column->dataValue('model_class')
                : $column->relation()->table()->ucFirstSingular();

            $result = $result->newLine()->newLine()->append(
                $stubBuilder->getFromStub([
                    '{relation}' => $relation,
                    '{relation_model}' => $relationModel,
                    '{relation_column}' => $relationColumn,
                ])
            );
        }

        return $result->value();
    }
}
