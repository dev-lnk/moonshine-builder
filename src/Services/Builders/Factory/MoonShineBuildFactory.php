<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders\Factory;

use DevLnk\LaravelCodeBuilder\Exceptions\NotFoundBuilderException;
use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\Builders\Factory\AbstractBuildFactory;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\MigrationBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\MoonShineModelBuilderContract;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\ResourceBuilderContract;

final readonly class MoonShineBuildFactory extends AbstractBuildFactory
{
    /**
     * @throws NotFoundBuilderException
     */
    public function call(string $buildType, string $stub): void
    {
        $classParameters = [
            'codeStructure' => $this->codeStructure,
            'codePath' => $this->codePath,
            'stubFile' => $stub,
        ];

        /**
         * @var AbstractBuilder $builder
         */
        $builder = match($buildType) {
            MoonShineBuildType::MODEL->value => app(
                MoonShineModelBuilderContract::class,
                $classParameters
            ),
            MoonShineBuildType::RESOURCE->value => app(
                ResourceBuilderContract::class,
                $classParameters
            ),
            MoonShineBuildType::MIGRATION->value => app(
                MigrationBuilderContract::class,
                $classParameters
            ),
            default => throw new NotFoundBuilderException()
        };

        $builder->build();
    }
}
