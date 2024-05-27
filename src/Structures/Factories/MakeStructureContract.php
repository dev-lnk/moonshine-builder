<?php

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Structures\CodeStructureList;

interface MakeStructureContract
{
    public function makeStructures(): CodeStructureList;
}
