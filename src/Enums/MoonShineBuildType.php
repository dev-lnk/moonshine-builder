<?php

namespace DevLnk\MoonShineBuilder\Enums;

use DevLnk\LaravelCodeBuilder\Enums\BuildTypeContract;

enum MoonShineBuildType: string implements BuildTypeContract
{
    case MODEL = 'model';

    case MIGRATION = 'migration';

    case RESOURCE = 'resource';

    public function stub(): string
    {
        return match ($this) {
            self::MODEL => 'Model',
            self::MIGRATION => 'Migration',
            self::RESOURCE => 'ModelResourceDefault',
        };
    }

    public function value(): string
    {
        return $this->value;
    }
}
