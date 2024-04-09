<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\MoonShine;
use DevLnk\MoonShineBuilder\Commands\ProjectBuildCommand;

class MoonShineBuilderProvider extends ServiceProvider
{
    protected array $commands = [
        ProjectBuildCommand::class,
    ];

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands($this->commands);
        }

        $this->publishes([
            __DIR__.'/../../config/moonshine_builder.php' =>
                config_path('moonshine_builder.php'),
        ], 'moonshine-builder');

        $this->mergeConfigFrom(
            __DIR__.'/../../config/moonshine_builder.php',
            'moonshine_builder'
        );
    }
}