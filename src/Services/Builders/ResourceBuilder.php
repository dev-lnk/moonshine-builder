<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Services\Builders;

use DevLnk\LaravelCodeBuilder\Services\Builders\AbstractBuilder;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Services\Builders\Contracts\ResourceBuilderContract;
use DevLnk\MoonShineBuilder\Structures\MoonShineStructure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;

class ResourceBuilder extends AbstractBuilder implements ResourceBuilderContract
{
    private MoonShineStructure $moonShineStructure;

    /**
     * @throws FileNotFoundException
     * @throws ProjectBuilderException
     */
    public function build(): void
    {
        $this->moonShineStructure = new MoonShineStructure($this->codeStructure);

        $resourcePath = $this->codePath->path(MoonShineBuildType::RESOURCE->value);
        $modelPath = $this->codePath->path(MoonShineBuildType::MODEL->value);

        $modelUse = class_exists($modelPath->namespace() . '\\' . $modelPath->rawName())
            ? "\nuse {$modelPath->namespace()}\\{$modelPath->rawName()};"
            : "";

        $withArray = $this->withArray();

        StubBuilder::make($this->stubFile)
            ->setKey(
                '{column}',
                str('')
                    ->newLine()
                    ->newLine()
                    ->append("\t")
                    ->append("protected string \$column = '{$this->codeStructure->dataValue('column')}';")
                    ->value(),
                ! is_null($this->codeStructure->dataValue('column'))
            )
            ->setKey(
                '{model_use}',
                $modelUse,
                ! empty($modelUse)
            )
            ->setKey(
                '{todo_model_not_found}',
                "// TODO model not found\n\t",
                empty($modelUse)
            )
            ->setKey(
                '{with_array}',
                "\n\n\tprotected array \$with = [{with}];",
                ! empty($withArray)
            )
            ->makeFromStub($resourcePath->file(), [
                '{namespace}' => $resourcePath->namespace(),
                '{field_uses}' => $this->usesFieldsToResource(),
                '{class}' => $resourcePath->rawName(),
                '{model}' => $modelPath->rawName(),
                '{with}' => $this->withArray(),
                '{fields}' => $this->columnsToResource(),
                '{rules}' => $this->columnsToRules(),
            ]);
    }

    /**
     * @throws ProjectBuilderException
     */
    protected function usesFieldsToResource(): string
    {
        $result = "";

        foreach ($this->moonShineStructure->getUsesForFields() as $use) {
            $result .= str($use)->newLine()->value();
        }

        return $result;
    }

    /**
     * @throws ProjectBuilderException
     */
    protected function columnsToResource(): string
    {
        $result = "";

        foreach ($this->moonShineStructure->getFields(tabulation: 5) as $field) {
            $result .= str($field)
                ->prepend("\t\t\t\t")
                ->prepend("\n")
                ->append(',')
                ->value()
            ;
        }

        return $result;
    }

    public function columnsToRules(): string
    {
        $result = "";

        foreach ($this->moonShineStructure->getRules() as $rule) {
            $result .= str($rule)
                ->prepend("\t\t\t")
                ->prepend("\n")
                ->append(',')
                ->value()
            ;
        }

        return $result;
    }

    public function withArray(): string
    {
        $withArray = array_map(fn($with) => "'$with'", $this->moonShineStructure->getWithProperty());
        return implode(', ', $withArray);
    }
}
