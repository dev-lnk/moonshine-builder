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
    public function getStructure(string $target, string $type): MainStructure
    {
        if($type === 'table') {
            return StructureFromTable::make($target)->makeStructure();
        }

        $path = config('moonshine_builder.builds_dir') . '/' . $target;

        if(! file_exists($path)) {
            throw new ProjectBuilderException("File $path not found");
        }

        $extension = pathinfo($path, PATHINFO_EXTENSION);

        return match ($extension) {
            'json' => StructureFromJson::make($path)->makeStructure(),
            default => throw new ProjectBuilderException("$extension extension is not supported")
        };
    }
}
