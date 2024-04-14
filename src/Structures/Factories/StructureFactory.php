<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\MoonShineBuilder\Structures\MainStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Traits\Makeable;

final class StructureFactory
{
    use Makeable;

    /**
     * @throws ProjectBuilderException
     */
    public function getStructure(string $file): MainStructure
    {
        $fileSeparate = explode('.', $file);
        $extension = $fileSeparate[count($fileSeparate) - 1];

        if($extension === 'table') {
            return StructureFromTable::make($file)->makeStructure();
        }

        $path = config('moonshine_builder.builds_dir') . '/' . $file;

        if(! file_exists($path)) {
            throw new ProjectBuilderException("File $path not found");
        }

        return match ($extension) {
            'json' => StructureFromJson::make($path)->makeStructure(),
            default => throw new ProjectBuilderException("$extension extension is not supported")
        };
    }
}