<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;

final class CodeStructureList
{
    /**
     * @var array<int, CodeStructure>
     */
    private array $codeStructures = [];

    public function addCodeStructure(CodeStructure $codeStructure): void
    {
        $this->codeStructures[] = $codeStructure;
    }

    /**
     * @return array<int, CodeStructure>
     */
    public function codeStructures(): array
    {
        return $this->codeStructures;
    }
}
