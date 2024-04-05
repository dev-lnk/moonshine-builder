<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Structures;

final class MainStructure
{
    /**
     * @var ResourceStructure[]
     */
    private array $resources = [];

    public function addResource(ResourceStructure $resourceBuilder): self
    {
        $this->resources[] = $resourceBuilder;
        return $this;
    }

    /**
     * @return ResourceStructure[]
     */
    public function resources(): array
    {
        return $this->resources;
    }
}