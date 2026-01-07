<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Enums\BuildType;
use DevLnk\MoonShineBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromMysql;
use Illuminate\Support\Facades\Schema;
use function Laravel\Prompts\{select};

class TableBuildCommand extends AbstractBuildCommand
{
    protected $signature = 'moonshine:build-table {target?}';

    public function handle(): int
    {
        $this->init();

        $table = $this->argument('target');

        $tables = collect(Schema::getTables())
            ->filter(fn ($v) => str_contains((string) $v['name'], (string) $table ?? ''))
            ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]);

        $table = $tables->count() === 1
            ? $tables->first()
            : select(
                'Table',
                collect(Schema::getTables())
                    ->filter(fn ($v) => str_contains((string) $v['name'], (string) $table ?? ''))
                    ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]),
            );

        if($table === null) {
            throw new CodeGenerateCommandException('Table not found');
        }

        $this->builders = array_filter($this->builders, fn ($item) => $item !== BuildType::MIGRATION);

        $codeStructures = StructureFromMysql::make(
            table: $table,
            entity: $table,
            isBelongsTo: true
        )
            ->makeStructures()
            ->codeStructures();

        foreach ($codeStructures as $codeStructure) {
            $this->make($codeStructure, $this->generationPath);
        }

        $this->resourceInfo();

        $this->components->info('All done');

        return self::SUCCESS;
    }
}
