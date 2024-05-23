<?php

namespace DevLnk\MoonShineBuilder\Traits;

use DevLnk\LaravelCodeBuilder\Enums\BuildTypeContract;
use DevLnk\LaravelCodeBuilder\Services\Builders\Factory\AbstractBuildFactory;
use DevLnk\LaravelCodeBuilder\Services\CodePath\CodePathContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Services\Builders\MoonShineBuildFactory;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShineCodePath;

trait CommandVariables
{
    protected function codePath(): CodePathContract
    {
        return new MoonShineCodePath();
    }

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