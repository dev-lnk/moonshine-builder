<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\CodePath;

use Carbon\Carbon;
use DevLnk\MoonShineBuilder\Exceptions\NotFoundCodePathException;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\DetailPagePath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\FormPagePath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\IndexPagePath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\MigrationPath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\ModelPath;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShine\ResourcePath;
use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructure;

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

    public function initPaths(CodeStructure $codeStructure): void
    {
        $time = Carbon::now();
        $time->addSeconds($this->iteration);

        $name = $codeStructure->entity()->ucFirstSingular();
        
        $this
            ->setPath(
                new ModelPath(
                    $name . '.php',
                    app_path('Models'),
                    'App\\Models'
                )
            )
            ->setPath(
                new ResourcePath(
                    $name . 'Resource.php',
                    app_path('MoonShine/Resources/' . $name),
                    'App\\MoonShine\\Resources\\' . $name
                )
            )
            ->setPath(
                new IndexPagePath(
                    $name . 'IndexPage.php',
                    app_path('MoonShine/Resources/' . $name . '/Pages'),
                    'App\\MoonShine\\Resources\\' . $name . '\\Pages'
                )
            )
            ->setPath(
                new FormPagePath(
                    $name . 'FormPage.php',
                    app_path('MoonShine/Resources/' . $name . '/Pages'),
                    'App\\MoonShine\\Resources\\' . $name . '\\Pages'
                )
            )
            ->setPath(
                new DetailPagePath(
                    $name . 'DetailPage.php',
                    app_path('MoonShine/Resources/' . $name . '/Pages'),
                    'App\\MoonShine\\Resources\\' . $name . '\\Pages'
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
