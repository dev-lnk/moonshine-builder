<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\CodePath;

use Carbon\Carbon;
use DevLnk\LaravelCodeBuilder\Exceptions\NotFoundCodePathException;
use DevLnk\LaravelCodeBuilder\Services\CodePath\AbstractPathItem;
use DevLnk\LaravelCodeBuilder\Services\CodePath\CodePathContract;
use DevLnk\LaravelCodeBuilder\Services\CodePath\CodePathItemContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\MigrationPath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\ModelPath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\ResourcePath;

class MoonShineCodePath implements CodePathContract
{
    public function __construct(
        private readonly int $iteration
    ) {
    }

    /**
     * @var array<string, CodePathItemContract>
     */
    private array $paths = [];

    public function initPaths(CodeStructure $codeStructure, string $generationPath, bool $isGenerationDir): void
    {
        $time = Carbon::now();
        $time->addSeconds($this->iteration);

        $this
            ->setPath(
                new ModelPath(
                    $codeStructure->entity()->ucFirstSingular() . '.php',
                    app_path('Models'),
                    'App\\Models'
                )
            )
            ->setPath(
                new ResourcePath(
                    $codeStructure->entity()->ucFirstSingular() . 'Resource.php',
                    app_path('MoonShine/Resources'),
                    'App\\MoonShine\\Resources'
                )
            )
            ->setPath(
                new MigrationPath(
                    $time->format('Y_m_d_His') . '_create_' . $codeStructure->table() . '.php',
                    base_path('database/migrations'),
                    ''
                )
            )
        ;
    }

    public function setPath(AbstractPathItem $path): self
    {
        if(isset($this->paths[$path->getBuildAlias()])) {
            return $this;
        }
        $this->paths[$path->getBuildAlias()] = $path;

        return $this;
    }

    /**
     * @throws NotFoundCodePathException
     */
    public function path(string $alias): CodePathItemContract
    {
        return $this->paths[$alias] ?? throw new NotFoundCodePathException("CodePath alias '$alias' not found");
    }
}
