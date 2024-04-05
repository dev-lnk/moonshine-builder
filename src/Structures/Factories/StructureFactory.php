<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures\Factories;

use MoonShine\ProjectBuilder\Structures\MainStructure;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;
use MoonShine\ProjectBuilder\Traits\Makeable;

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