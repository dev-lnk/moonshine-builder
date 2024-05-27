<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\CodeStructureList;

final class MoonShineStructureFactory
{
    /**
     * @throws ProjectBuilderException
     */
    public function getStructures(string $target): CodeStructureList
    {
        $path = config('moonshine_builder.builds_dir') . '/' . $target;

        if(! file_exists($path)) {
            throw new ProjectBuilderException("File $path not found");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'json' => StructureFromJson::make($path)->makeStructures(),
            default => throw new ProjectBuilderException("$extension extension is not supported")
        };
    }
}
