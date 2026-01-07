<?php

namespace DevLnk\MoonShineBuilder\Commands;

class TableBuildCommand extends MoonShineBuildCommand
{
    protected $signature = 'moonshine:build-table {table?}';

    public function handle(): int
    {
        $this->init();

        return self::SUCCESS;
    }
}
