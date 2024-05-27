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

    private bool $withModel = true;

    private bool $withMigration = true;

    private bool $withResource = true;

    public function addCodeStructure(CodeStructure $codeStructure): void
    {
        $this->codeStructures[] = $codeStructure;
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
     * @return array<int, CodeStructure>
     */
    public function codeStructures(): array
    {
        return $this->codeStructures;
    }
}
