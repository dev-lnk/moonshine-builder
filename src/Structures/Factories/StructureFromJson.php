<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Structures\Factories;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\ColumnStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\RelationStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\CodeStructureList;

final readonly class StructureFromJson implements MakeStructureContract
{
    public function __construct(
        private string $filePath
    ) {
    }

    public static function make(string $filePath): static
    {
        return new static($filePath);
    }

    /**
     * @throws ProjectBuilderException
     */
    public function makeStructures(): CodeStructureList
    {
        if(! file_exists($this->filePath)) {
            throw new ProjectBuilderException('File not available: ' . $this->filePath);
        }

        $file = json_decode(file_get_contents($this->filePath), true);

        if(json_last_error() !== JSON_ERROR_NONE) {
            throw new ProjectBuilderException('Wrong json data');
        }

        return (new StructureFromArray($file))->makeStructures();
    }
}
