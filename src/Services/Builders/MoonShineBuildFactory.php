<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Exceptions\NotFoundBuilderException;
use DevLnk\LaravelCodeBuilder\Services\Builders\Factory\AbstractBuildFactory;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

final readonly class MoonShineBuildFactory extends AbstractBuildFactory
{
    /**
     * @throws NotFoundBuilderException
     * @throws FileNotFoundException
     */
    public function call(string $buildType, string $stub): void
    {
        $builder = match($buildType) {
            MoonShineBuildType::MODEL->value => new ModelBuilder(
                $this->codeStructure,
                $this->codePath,
                $stub
            ),
            MoonShineBuildType::RESOURCE->value => new ResourceBuilder(
                $this->codeStructure,
                $this->codePath,
                $stub
            ),
            MoonShineBuildType::MIGRATION->value => new MigrationBuilder(
                $this->codeStructure,
                $this->codePath,
                $stub
            ),
            default => throw new NotFoundBuilderException()
        };

        $builder->build();
    }
}
