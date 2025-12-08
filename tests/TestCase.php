<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests;

use DevLnk\MoonShineBuilder\Providers\MoonShineBuilderProvider;
use DevLnk\MoonShineBuilder\Tests\Fixtures\TestServiceProvider;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use MoonShine\Laravel\Commands\InstallCommand;
use MoonShine\Laravel\Providers\MoonShineServiceProvider;

class TestCase extends \Orchestra\Testbench\TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->performApplication();
    }

    protected function setUpTraits(): array
    {
        (new Filesystem())->cleanDirectory(base_path('database/migrations'));

        return parent::setUpTraits();
    }

    protected function performApplication(): static
    {
        Config::set('moonshine.dir', 'vendor/orchestra/testbench-core/laravel/app/MoonShine');

        $this->artisan(InstallCommand::class, [
            '--tests-mode' => true,
        ]);

        $this->artisan('vendor:publish --tag=moonshine-builder');

        $this->artisan('optimize:clear');

        $dir = base_path('builds');
        if(! is_dir($dir)) {
            mkdir($dir);
        }

        copy(realpath('./tests/Fixtures/builds/project.json'), base_path('builds/project.json'));
        copy(realpath('./tests/Fixtures/builds/belongs_to_many.json'), base_path('builds/belongs_to_many.json'));
        copy(realpath('./tests/Fixtures/builds/todo.json'), base_path('builds/todo.json'));

        return $this;
    }

    /**
     * @param string   $filePath
     * @param string[] $fileMustContains
     *
     * @throws FileNotFoundException
     */
    protected function testBuildFile(string $filePath, array $fileMustContains): void
    {
        $this->assertFileExists($filePath);
        $resource = (new Filesystem())->get($filePath);
        foreach ($fileMustContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }
    }

    protected function getMigrationFile(string $migrationPath, string $migrationName): string
    {
        $migrationFile = '';
        $migrations = (new Filesystem())->allFiles($migrationPath);
        foreach ($migrations as $migration) {
            if(str_contains((string) $migration, $migrationName)) {
                $migrationFile = (string) $migration;
                break;
            }
        }
        return $migrationFile;
    }

    protected function getPackageProviders($app): array
    {
        return [
            MoonShineServiceProvider::class,
            MoonShineBuilderProvider::class,
            TestServiceProvider::class,
        ];
    }
}
