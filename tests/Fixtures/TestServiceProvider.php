<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests\Fixtures;

use Illuminate\Support\ServiceProvider;

class TestServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(realpath('./tests/Fixtures/Migrations'));
    }
}
