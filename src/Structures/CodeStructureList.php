<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\RelationStructure;

final class CodeStructureList
{
    /**
     * @var array<int, CodeStructure>
     */
    private array $codeStructures = [];

    public function addCodeStructure(CodeStructure $codeStructure): void
    {
        $this->codeStructures[] = $codeStructure;
    }

    /**
     * @return array<int, CodeStructure>
     */
    public function codeStructures(): array
    {
        return $this->codeStructures;
    }

    public function toJson(array $pivotTables = []): string
    {
        $resources = [];

        if(! empty($pivotTables)) {
            foreach ($this->codeStructures as $key => $codeStructure) {
                if(! in_array($codeStructure->table(), $pivotTables)) {
                    continue;
                }

                $pivotColumns = [];
                foreach ($codeStructure->columns() as $column) {
                    if($column->type() !== SqlTypeMap::BELONGS_TO) {
                        continue;
                    }
                    $pivotColumns[] = [
                        str($column->relation()->table()->camel())->singular()->value(), // entity name
                        $column->relation()->table()->raw(), // table name
                    ];
                }

                if(count($pivotColumns) !== 2) {
                    unset($this->codeStructures[$key]);
                    continue;
                }

                /**
                 * Result for example:
                 * <code>
                 * [
                 *  'item'     => ['table' => 'properties'],
                 *  'property' => ['table' => 'items']
                 * ]
                 * </code>
                 */
                $pivotColumnsResult = [];
                $pivotColumnsResult[$pivotColumns[0][0]] = [
                    'table' => $pivotColumns[1][1],
                ];
                $pivotColumnsResult[$pivotColumns[1][0]] = [
                    'table' => $pivotColumns[0][1],
                ];

                foreach ($this->codeStructures as $findCodeStructure) {
                    if(! isset($pivotColumnsResult[$findCodeStructure->entity()->raw()])) {
                        continue;
                    }

                    $relationColumn = $pivotColumnsResult[$findCodeStructure->entity()->raw()]['table'];

                    $field = new ColumnStructure(
                        column: $relationColumn,
                        name: '',
                        type: SqlTypeMap::BELONGS_TO_MANY,
                        default: null,
                        nullable: true
                    );
                    $field->setRelation(new RelationStructure('id', $relationColumn));

                    $findCodeStructure->addColumn($field);
                }

                unset($this->codeStructures[$key]);
            }
        }

        foreach ($this->codeStructures as $codeStructure) {
            $fields = [];
            foreach ($codeStructure->columns() as $column) {
                $field = [
                    'column' => $column->column(),
                    'type' => $column->type()->value,
                    'default' => $column->default(),
                    'name' => $column->name(),
                ];

                $resourceColumnProperties = [
                    'name',
                    'title'
                ];

                if(in_array($column->column(), $resourceColumnProperties)) {
                    $codeStructure->setDataValue('column', $column->column());
                }

                if($column->relation()) {
                    $field['relation']['table'] = $column->relation()->table()->raw();
                    $field['relation']['foreign_column'] = $column->relation()->foreignColumn();
                }

                if(
                    $column->column() === 'moonshine_user_id'
                    && $column->type() === SqlTypeMap::BELONGS_TO
                ) {
                    $field['resource_class'] = "\\MoonShine\\Resources\\MoonShineUserResource";
                }

                $fields[] = $field;
            }

            $resources[] = [
                'name' => $codeStructure->entity()->ucFirst(),
                'timestamps' => $codeStructure->isTimestamps(),
                'soft_deletes' => $codeStructure->isSoftDeletes(),
                'column' => $codeStructure->dataValue('column'),
                'withModel' => false,
                'withMigration' => false,
                'fields' => $fields,
            ];
        }

        return json_encode(['resources' => $resources]);
    }
}
