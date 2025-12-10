<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Providers;

use DevLnk\MoonShineBuilder\Commands\ModelBuildCommand;
use DevLnk\MoonShineBuilder\Commands\MoonShineBuildCommand;
use DevLnk\MoonShineBuilder\Commands\MoonShineProjectSchemaCommand;
use DevLnk\MoonShineBuilder\Commands\ResourceBuildCommand;
use DevLnk\MoonShineBuilder\Commands\TypeCommand;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\DetailPageBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\FormPageBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\IndexPageBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\MigrationBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\ModelBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\ResourceBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\DetailPageBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\FormPageBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\IndexPageBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\MigrationBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\ModelBuilder;
use DevLnk\MoonShineBuilder\Services\Builders\ResourceBuilder;
use Illuminate\Support\ServiceProvider;

class MoonShineBuilderProvider extends ServiceProvider
{
    protected array $commands = [
        MoonShineBuildCommand::class,
        MoonShineProjectSchemaCommand::class,
        ResourceBuildCommand::class,
        ModelBuildCommand::class,
        TypeCommand::class,
    ];

    public function register(): void
    {
        $this->app->bind(ModelBuilderContract::class, ModelBuilder::class);
        $this->app->bind(ResourceBuilderContract::class, ResourceBuilder::class);
        $this->app->bind(MigrationBuilderContract::class, MigrationBuilder::class);
        $this->app->bind(IndexPageBuilderContract::class, IndexPageBuilder::class);
        $this->app->bind(FormPageBuilderContract::class, FormPageBuilder::class);
        $this->app->bind(DetailPageBuilderContract::class, DetailPageBuilder::class);
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
