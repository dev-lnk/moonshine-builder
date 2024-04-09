<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

final class MainStructure
{
    /**
     * @var array<int, ResourceStructure>
     */
    private array $resources = [];

    public function addResource(ResourceStructure $resourceBuilder): self
    {
        $this->resources[] = $resourceBuilder;
        return $this;
    }

    /**
     * @return array<int, ResourceStructure>
     */
    public function resources(): array
    {
        return $this->resources;
    }
}