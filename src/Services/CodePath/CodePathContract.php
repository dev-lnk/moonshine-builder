<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\CodePath;

use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructure;

interface CodePathContract
{
    public function initPaths(CodeStructure $codeStructure): void;

    public function setPath(AbstractPathItem $path): CodePathContract;

    public function path(string $alias): CodePathItemContract;
}
