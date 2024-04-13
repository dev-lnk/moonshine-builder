<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures;

final class MainStructure
{
    /**
     * @var array<int, ResourceStructure>
     */
    private array $resources = [];

    private bool $withModel = true;

    private bool $withMigration = true;

    private bool $withResource = true;

    public function addResource(ResourceStructure $resourceBuilder): void
    {
        $this->resources[] = $resourceBuilder;
    }

    public function setWithModel(bool $withModel): void
    {
        $this->withModel = $withModel;
    }

    public function setWithMigration(bool $withMigration): void
    {
        $this->withMigration = $withMigration;
    }

    public function setWithResource(bool $withResource): void
    {
        $this->withResource = $withResource;
    }

    public function withModel(): bool
    {
        return $this->withModel;
    }

    public function withMigration(): bool
    {
        return $this->withMigration;
    }

    public function withResource(): bool
    {
        return $this->withResource;
    }

    /**
     * @return array<int, ResourceStructure>
     */
    public function resources(): array
    {
        return $this->resources;
    }
}