<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Enums\LaravelSqlType;
use DevLnk\MoonShineBuilder\Structures\FieldStructure;
use DevLnk\MoonShineBuilder\Structures\MainStructure;
use DevLnk\MoonShineBuilder\Structures\ResourceStructure;
use DevLnk\MoonShineBuilder\Traits\Makeable;
use Illuminate\Support\Facades\Schema;

final class StructureFromTable implements MakeStructureContract
{
    use Makeable;

    public function __construct(
        private string $table
    ) {
        $this->table = str_replace('.table', '', $this->table);
    }

    public function makeStructure(): MainStructure
    {
        $mainStructure = new MainStructure();
        $mainStructure->setWithMigration(false);
        $mainStructure->setWithModel(false);

        $resourceName = str($this->table)->singular()->ucfirst()->append('Resource')->value();

        $resourceStructure = new ResourceStructure($resourceName);

        $mainStructure->addResource($resourceStructure);

        $columns = Schema::getColumns($this->table);

        $indexes = Schema::getIndexes($this->table);

        $primaryKey = 'id';
        foreach ($indexes as $index) {
            if($index['name'] === 'primary') {
                $primaryKey = $index['columns'][0];
                break;
            }
        }

        foreach ($columns as $column) {
            $fieldStructure = new FieldStructure($column['name']);

            $type = $column['name'] === $primaryKey
                ? 'primary'
                : preg_replace("/[0-9]+|\(|\)/", '', $column['type']);

            $fieldStructure->setType(LaravelSqlType::fromSqlType($type)->value);

            $resourceStructure->addField($fieldStructure);
        }

        return $mainStructure;
    }
}