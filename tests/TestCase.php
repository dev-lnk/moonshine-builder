<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests;

use DevLnk\MoonShineBuilder\Providers\MoonShineBuilderProvider;
use DevLnk\MoonShineBuilder\Tests\Fixtures\TestServiceProvider;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use MoonShine\Commands\InstallCommand;
use MoonShine\Providers\MoonShineServiceProvider;

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
            '--without-user' => true,
            '--without-migrations' => true,
        ]);

        $this->artisan('vendor:publish --tag=moonshine-builder');

        $this->artisan('config:clear');

        $dir = base_path('builds');
        if(! is_dir($dir)) {
            mkdir($dir);
        }

        copy(realpath('./tests/Fixtures/builds/project.json'), base_path('builds/project.json'));

        return $this;
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
