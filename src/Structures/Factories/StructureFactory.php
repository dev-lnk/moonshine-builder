<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Structures\MainStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Traits\Makeable;

final class StructureFactory
{
    use Makeable;

    /**
     * @throws ProjectBuilderException
     */
    public function getBuilderFromJson(string $filePath): MainStructure
    {
        $fromJsonBuilder = new StructureFromJson($filePath);
        return $fromJsonBuilder->makeStructure();
    }
}