<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Enums;

enum ParseType: string
{
    case TABLE = 'table';

    case JSON = 'json';

    //case OPENAPI = 'openapi';

    case CONSOLE = 'console';

    case MODEL = 'model';

    public function toString(): string
    {
        return match ($this) {
            self::TABLE => 'table',
            self::JSON => 'json',
            self::CONSOLE => 'console',
            self::MODEL => 'model',
            //self::OPENAPI => 'openapi yaml (beta)',
        };
    }
}
