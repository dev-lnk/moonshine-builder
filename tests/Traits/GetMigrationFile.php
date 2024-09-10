<?php

namespace DevLnk\MoonShineBuilder\Tests\Traits;

use Illuminate\Filesystem\Filesystem;

trait GetMigrationFile
{
    protected function getMigrationFile(string $migrationName, string $migrationPath, Filesystem $filesystem): string
    {
        $migrationFile = '';
        $migrations = $filesystem->allFiles($migrationPath);
        foreach ($migrations as $migration) {
            if (str_contains($migration, $migrationName)) {
                $migrationFile = $migration;

                break;
            }
        }

        return $migrationFile;
    }
}
