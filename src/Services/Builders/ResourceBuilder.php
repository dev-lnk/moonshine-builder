<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\Builders\Core\Contracts\EditActionBuilderContract;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourceBuilder extends AbstractBuilder implements EditActionBuilderContract
{
    /**
     * @throws FileNotFoundException
     */
    public function build(): void
    {
        $resourcePath = $this->codePath->path(MoonShineBuildType::RESOURCE->value);
        $modelPath = $this->codePath->path(MoonShineBuildType::MODEL->value);

        // TODO uses fields column
        StubBuilder::make($this->stubFile)
            ->makeFromStub($resourcePath->file(), [
                '{namespace}' => $resourcePath->namespace(),
                '{model_namespace}' => $modelPath->namespace() . '\\' . $modelPath->rawName(),
                '{uses}' => '',
                '{class}' => $resourcePath->rawName(),
                '{model}' => $modelPath->rawName(),
                '{column}' => '',
                '{fields}' => ''
            ]);
    }
}