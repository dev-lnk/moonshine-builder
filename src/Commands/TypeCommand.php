<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use Illuminate\Console\Command;

class TypeCommand extends Command
{
    protected $signature = 'ms-build:types';

    public function handle(): void
    {
        $types = array_map(static fn ($value) => $value->value, SqlTypeMap::cases());

        $this->components->bulletList($types);
    }
}
