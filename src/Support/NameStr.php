<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Support;

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

    public function ucFirst(): string
    {
        return str($this->raw())->ucfirst()->value();
    }

    public function lower(): string
    {
        return str($this->raw())->snake()->lower()->value();
    }

    public function plural(): string
    {
        return str($this->lower())->plural()->value();
    }
}