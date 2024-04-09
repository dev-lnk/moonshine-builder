<?php

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Structures\MainStructure;

interface MakeStructureContract
{
    public function makeStructure(): MainStructure;
}