<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\Builders\Core\Contracts\EditActionBuilderContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class MigrationBuilder extends AbstractBuilder implements EditActionBuilderContract
{
    /**
     * @throws FileNotFoundException
     */
    public function build(): void
    {
        $migrationPath = $this->codePath->path(MoonShineBuildType::MIGRATION->value);

        StubBuilder::make($this->stubFile)
            ->setKey(
                '{timestamps}',
                PHP_EOL . "\t\t\t\$table->timestamps();",
                $this->codeStructure->isTimestamps()
            )
            ->setKey(
                '{soft_deletes}',
                PHP_EOL . "\t\t\t\$table->softDeletes();",
                $this->codeStructure->isSoftDeletes()
            )
            ->makeFromStub($migrationPath->file(), [
                '{table}' => $this->codeStructure->table(),
                '{columns}' => $this->columnsToMigration(),
            ]);
    }

    protected function columnsToMigration(): string
    {
        $result = "";

        foreach ($this->codeStructure->columns() as $column) {
            if(
                $column->type() === SqlTypeMap::HAS_ONE
                || $column->type() === SqlTypeMap::HAS_MANY
            ) {
                continue;
            }

            if($this->codeStructure->isTimestamps()
                && ($column->isCreatedAt() || $column->isUpdatedAt())
            ) {
                continue;
            }

            if($this->codeStructure->isSoftDeletes() && $column->isDeletedAt()) {
                continue;
            }

            $result .= str('$table->')
                ->prepend("\t\t\t")
                ->prepend("\n")
                ->append($this->migrationName($column))
                ->append($this->migrationMethods($column))
                ->append(';')
                ->value()
            ;
        }

        return $result;
    }

    protected function migrationName(ColumnStructure $column): string
    {
        if($column->relation()) {
            return $this->migrationNameFromRelation($column);
        }

        return str($column->type()->value)
            ->when(
                $column->column() === 'id' && $column->type()->value === 'id',
                fn ($str) => $str->append("("),
                fn ($str) => $str->append("('{$column->column()}'")
            )
            ->when(
                ! is_null($column->dataValue('migration_options')),
                fn ($str) => $str->append(', ' . implode(', ', $column->dataValue('migration_options')) . ')'),
                fn ($str) => $str->append(")")
            )
            ->value()
        ;
    }

    public function migrationNameFromRelation(ColumnStructure $column): string
    {
        if($column->type() !== SqlTypeMap::BELONGS_TO) {
            return '';
        }

        $modelName = str($column->relation()->table()->singular())->ucfirst()->value();

        $modelClass = empty($column->dataValue('model_class')) ? '\\App\\Models\\' : $column->dataValue('model_class');

        return str('foreignIdFor')
            ->append('(')
            ->append($modelClass)
            ->when(
                empty($column->dataValue('model_class')),
                fn ($str) => $str->append($modelName)
            )
            ->append("::class")
//            ->when($this->foreignId,
//                fn($str) => $str->append(", '{$this->foreignId}'")
//            )
            ->append(')')
            ->newLine()
            ->append("\t\t\t\t")
            ->append('->constrained()')
            ->newLine()
            ->append("\t\t\t\t")
            ->append('->cascadeOnDelete()')
            ->newLine()
            ->append("\t\t\t\t")
            ->append('->cascadeOnUpdate()')
            ->value()
        ;
    }

    protected function migrationMethods(ColumnStructure $column): string
    {
        if(
            is_null($column->dataValue('migration_methods'))
            || ! is_array($column->dataValue('migration_methods'))
        ) {
            return '';
        }

        $result = "";

        foreach ($column->dataValue('migration_methods') as $method) {
            if(! str_contains($method, '(')) {
                $method .= "()";
            }
            $result .= "->$method";

        }

        return $result;
    }
}
