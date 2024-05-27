<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\CodePath\MoonShine;

use DevLnk\LaravelCodeBuilder\Services\CodePath\AbstractPathItem;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;

readonly class MigrationPath extends AbstractPathItem
{
    public function getBuildAlias(): string
    {
        return MoonShineBuildType::MIGRATION->value;
    }
}
