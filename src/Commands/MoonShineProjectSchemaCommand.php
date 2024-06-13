<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Services\CodeStructure\Factories\CodeStructureFromMysql;
use DevLnk\MoonShineBuilder\Structures\CodeStructureList;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\multiselect;

class MoonShineProjectSchemaCommand extends Command
{
    protected $signature = 'moonshine:project-schema';

    protected array $systemTables = [
        'cache',
        'cache_locks',
        'failed_jobs',
        'jobs',
        'job_batches',
        'migrations',
        'moonshine_socialites',
        'moonshine_users',
        'moonshine_user_roles',
        'notifications',
        'password_reset_tokens',
        'sessions',
        'telescope_entries',
        'telescope_entries_tags',
        'telescope_monitoring',
    ];

    public function handle(): int
    {
        $tablesList = collect(Schema::getTables())
            ->filter(fn ($v) => ! in_array($v['name'], $this->systemTables))
            ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]);

        $pivotTables = multiselect(
            'Select the pivot table to correctly generate BelongsToMany (Press enter to skip)',
            $tablesList,
            []
        );

        $this->systemTables = array_merge($this->systemTables, $pivotTables);
        $tablesList = collect(Schema::getTables())
            ->filter(fn ($v) => ! in_array($v['name'], $this->systemTables))
            ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]);

        $tables = multiselect(
            'Select tables',
            $tablesList,
            $tablesList
        );

        $codeStructures = new CodeStructureList();
        $tables = array_merge($tables, $pivotTables);

        foreach ($tables as $table) {
            $entity = str($table)->singular()->camel()->ucfirst()->value();

            $codeStructures->addCodeStructure(CodeStructureFromMysql::make(
                table: (string) $table,
                entity: $entity,
                isBelongsTo: true,
                hasMany: [],
                hasOne: [],
                belongsToMany: []
            ));
        }

        $dir = config('moonshine_builder.builds_dir');

        $fileName = "project_" . date('YmdHis') . ".json";

        (new Filesystem())->put("$dir/$fileName", $codeStructures->toJson($pivotTables));

        $this->warn("$fileName was created successfully! To generate resources, run: ");
        $this->info("php artisan moonshine:build $fileName");

        return self::SUCCESS;
    }
}
