<?php

namespace MoonShine\ProjectBuilder\Structures\Factories;

use MoonShine\ProjectBuilder\Structures\MainStructure;

interface MakeStructureContract
{
    public function makeStructure(): MainStructure;
}