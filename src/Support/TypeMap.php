<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Support;

use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Text;

final class TypeMap
{
    public static function get(): array
    {
        return [
            ID::class => [
                'id',
            ],
            Number::class => [
                'unsignedBigInteger',
                'unsignedInteger',
                'unsignedMediumInteger',
                'unsignedSmallInteger',
                'unsignedTinyInteger',
                'bigInteger',
                'integer',
                'tinyInteger',
            ],
            Text::class => [
                'string',
                'text',
            ],
            BelongsTo::class => [
                'BelongsTo'
            ],
            HasMany::class => [
                'HasMany'
            ],
            HasOne::class => [
                'HasOne'
            ],
        ];
    }
}