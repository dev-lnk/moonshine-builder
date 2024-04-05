<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Traits;

trait Makeable
{
    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }
}