<?php

namespace DevLnk\MoonShineBuilder\Traits;

use DevLnk\LaravelCodeBuilder\Enums\BuildTypeContract;
use DevLnk\LaravelCodeBuilder\Services\Builders\Factory\AbstractBuildFactory;
use DevLnk\LaravelCodeBuilder\Services\CodePath\CodePathContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Services\Builders\MoonShineBuildFactory;

trait CommandVariables
{
    protected function buildFactory(
        CodeStructure $codeStructure,
        CodePathContract $codePath
    ): AbstractBuildFactory {
        return new MoonShineBuildFactory(
            $codeStructure,
            $codePath
        );
    }

    public function generationPath(): string
    {
        return '_default';
    }

    protected function setStubDir(): void
    {
        $this->stubDir = __DIR__ . '/../../stubs/';
    }

    protected function prepareBuilders(): void
    {
        $this->builders = $this->builders();
    }

    /**
     * @return array<int, BuildTypeContract>
     */
    protected function builders(): array
    {
        return [
            MoonShineBuildType::MODEL,
            MoonShineBuildType::RESOURCE,
            MoonShineBuildType::MIGRATION,
        ];
    }
}
