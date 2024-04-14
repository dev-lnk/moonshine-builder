<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Support;

use Illuminate\Support\Facades\File;
use MoonShine\MoonShine;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use Symfony\Component\Finder\SplFileInfo;

final class TypeMap
{
    /**
     * @var array<string, string>
     */
    private array $fieldClasses;

    public function __construct()
    {
        $this->fieldClasses = collect(File::files(MoonShine::path('src/Fields')))
            ->mapWithKeys(
                fn (SplFileInfo $file): array => [
                    $file->getFilenameWithoutExtension() => 'MoonShine\\Fields\\'.$file->getFilenameWithoutExtension(),
                ]
            )
            ->except(['Field', 'Fields', 'FormElement', 'FormElements'])
            ->toArray()
        ;
    }

    /**
     * @throws ProjectBuilderException
     */
    public function fieldClassFromAlias(string $field): string
    {
        return $this->fieldClasses[$field] ?? throw new ProjectBuilderException("Field: $field not found");
    }
}