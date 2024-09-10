<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\Factories\StructureFromConsole;

use function Laravel\Prompts\{search, text, confirm};

class ResourceBuildCommand extends MoonShineBuildCommand
{
    protected $signature = 'ms-build:resource {entity} {fields?*}';

    public function handle(): int
    {
        $this->setStubDir();

        $this->prepareBuilders();

        $fields = $this->hasArgument('fields') ? $this->consoleFields() : $this->promptFields();

        $isTimeStamps = confirm('Add timestamps?', true);
        $isSoftDeletes = confirm('Add softDelete?', false);
        $isMigration = confirm('Make migration?', false);

        $codeStructureList = (new StructureFromConsole(
            $this->argument('entity'),
            $fields,
            $isMigration,
            $isTimeStamps,
            $isSoftDeletes
        ))->makeStructures();

        $generationPath = $this->generationPath();

        $this->make($codeStructureList->codeStructures()[0], $generationPath);

        $this->components->info('All done');

        return self::SUCCESS;
    }

    /**
     * @throws ProjectBuilderException
     * @return array<int, array{column: string, name:string, type:string, relationTable:string}>
     */
    protected function consoleFields(): array
    {
        $consoleFields = $this->argument('fields');

        $result = [];

        foreach ($consoleFields as $value) {
            $field = explode(':', $value);
            if(count($field) < 2) {
                throw new ProjectBuilderException('Incorrect field value');
            }

            if(count($field) === 2) {
                $result[] = [
                    'column' => $field[0],
                    'name' => str($field[0])->ucfirst()->value(),
                    'type' => $field[1],
                    'table' => '',
                ];

                continue;
            }

            $result[] = [
                'column' => $field[0],
                'name' => $field[1],
                'type' => $field[2],
                'table'  => $field[3] ?? '',
            ];
        }

        return $result;
    }

    /**
     * @return array<int, array{column: string, name:string, type:string, relationTable:string}>
     */
    protected function promptFields(): array
    {
        $result = [];

        $sqlTypesValue = [];
        // Popular first
        $sqlTypesValue[] = SqlTypeMap::ID->value;
        $sqlTypesValue[] = SqlTypeMap::STRING->value;
        $sqlTypesValue[] = SqlTypeMap::TEXT->value;
        $sqlTypesValue[] = SqlTypeMap::BOOLEAN->value;
        $sqlTypesValue[] = SqlTypeMap::BIG_INTEGER->value;
        $sqlTypesValue[] = SqlTypeMap::BELONGS_TO->value;
        $sqlTypesValue[] = SqlTypeMap::HAS_MANY->value;
        $sqlTypesValue[] = SqlTypeMap::HAS_ONE->value;
        $sqlTypesValue[] = SqlTypeMap::BELONGS_TO_MANY->value;

        $sqlTypesValue = array_unique(
            array_merge($sqlTypesValue, array_map(static fn ($value) => $value->value, SqlTypeMap::cases()))
        );

        do {
            // Column
            $column = text('Column:');

            // Column name
            $defaultName = str($column)->replace('_id', '')->camel()->ucfirst()->value();
            $name = text('Column name:', $defaultName);

            // Selecting a field type
            $typeIndex = search('Column type:', static function (string $search) use ($sqlTypesValue) {
                return strlen($search) === 0
                    ? $sqlTypesValue
                    : array_filter($sqlTypesValue, fn($value) => str_contains($value, $search));
            }, scroll: 9);

            // If the type is relation, specify the table
            $sqlType = SqlTypeMap::from($sqlTypesValue[$typeIndex]);
            $relationTypes = [
                SqlTypeMap::BELONGS_TO,
                SqlTypeMap::BELONGS_TO_MANY,
                SqlTypeMap::HAS_ONE,
                SqlTypeMap::HAS_MANY,
            ];

            $relationTable = '';
            if(in_array($sqlType, $relationTypes)) {
                $tableName = str($column)->replace('_id', '')->snake()->plural();
                $relationTable = text('Table name:', $tableName);
            }

            $result[] = [
                'column' => $column,
                'name' => $name,
                'type' => $sqlTypesValue[$typeIndex],
                'relationTable' => $relationTable
            ];

            $addMore = confirm('Add more fields?', true);
        } while ($addMore);

        return $result;
    }
}
