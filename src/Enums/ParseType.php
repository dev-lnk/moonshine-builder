<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Enums;

enum ParseType: string
{
    case TABLE = 'table';

    case JSON = 'json';

    case CONSOLE = 'console';

    case MODEL = 'model';

    public function toString(): string
    {
        return match ($this) {
            self::TABLE     => 'table',
            self::JSON      => 'json',
            self::CONSOLE   => 'console',
            self::MODEL     => 'model',
        };
    }

    public function getCommandName(): string
    {
        return match ($this) {
            self::TABLE     => 'moonshine:build-table',
            self::JSON      => 'moonshine:build-json',
            self::CONSOLE   => 'moonshine:build-resource',
            self::MODEL     => 'moonshine:build-model',
        };
    }
}
