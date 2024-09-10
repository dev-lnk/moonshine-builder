<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Providers;

use DevLnk\MoonShineBuilder\Commands\MoonShineBuildCommand;
use DevLnk\MoonShineBuilder\Commands\MoonShineProjectSchemaCommand;
use DevLnk\MoonShineBuilder\Commands\ResourceBuildCommand;
use DevLnk\MoonShineBuilder\Commands\TypeCommand;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\MigrationBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\MoonShineModelBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\ResourceBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\MigrationBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\MoonShineModelBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\ResourceBuilder;
use Illuminate\Support\ServiceProvider;

class MoonShineBuilderProvider extends ServiceProvider
{
    protected array $commands = [
        MoonShineBuildCommand::class,
        MoonShineProjectSchemaCommand::class,
        ResourceBuildCommand::class,
        TypeCommand::class,
    ];

    public function register(): void
    {
        $this->app->bind(MoonShineModelBuilderContract::class, MoonShineModelBuilder::class);
        $this->app->bind(ResourceBuilderContract::class, ResourceBuilder::class);
        $this->app->bind(MigrationBuilderContract::class, MigrationBuilder::class);
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        $this->publishes([
            __DIR__ . '/../../config/moonshine_builder.php' =>
                config_path('moonshine_builder.php'),
        ], 'moonshine-builder');

        $this->mergeConfigFrom(
            __DIR__ . '/../../config/moonshine_builder.php',
            'moonshine_builder'
        );
    }
}
