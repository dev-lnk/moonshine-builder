<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Providers;

use Illuminate\Support\ServiceProvider;
use MoonShine\MoonShine;
use MoonShine\ProjectBuilder\Commands\ProjectBuildCommand;

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

        $this->mergeConfigFrom(
            __DIR__.'/../../config/moonshine_builder.php',
            'moonshine_builder'
        );
    }
}