<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Enums\BuildType;
use DevLnk\MoonShineBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\MoonShineBuilder\Exceptions\NotFoundBuilderException;
use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromMysql;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\Schema;
use MoonShine\Laravel\Commands\MoonShineCommand;
use DevLnk\MoonShineBuilder\Services\CodeGenerator;

use function Laravel\Prompts\{select};

class TableBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build-table {target?}';

    /**
     * @throws CodeGenerateCommandException
     * @throws FileNotFoundException
     * @throws NotFoundBuilderException
     */
    public function handle(CodeGenerator $codeGenerator): int
    {
        $codeGenerator->setCommand($this);

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

        $codeGenerator->replaceBuilder(
            array_filter($codeGenerator->getBuilders(), fn ($item) => $item !== BuildType::MIGRATION)
        );

        $codeStructures = StructureFromMysql::make(
            table: $table,
            entity: $table,
            isBelongsTo: true
        )
            ->makeStructures()
            ->codeStructures();

        foreach ($codeStructures as $codeStructure) {
            $codeGenerator->make($codeStructure);
        }

        $codeGenerator->resourceInfo();

        $this->components->info('All done');

        return self::SUCCESS;
    }
}
