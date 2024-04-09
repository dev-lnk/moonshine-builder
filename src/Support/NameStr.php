<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Support;

use Illuminate\Support\Stringable;

final class NameStr
{
    public function __construct(
        private readonly string $name
    ) {

    }

    public function raw(): string
    {
        return $this->name;
    }

    public function str(): Stringable
    {
        return str($this->name);
    }

    public function ucFirst(): string
    {
        return str($this->raw())->ucfirst()->value();
    }

    public function ucFirstSingular(): string
    {
        return str($this->raw())->singular()->ucfirst()->value();
    }

    public function lower(): string
    {
        return str($this->raw())
            ->snake()
            ->lower()
            ->value();
    }

    public function camel(): string
    {
        return str($this->raw())
            ->camel()
            ->value();
    }

    public function plural(): string
    {
        return str($this->lower())->plural()->value();
    }
}