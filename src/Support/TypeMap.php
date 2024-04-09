<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Support;

use MoonShine\Fields\Checkbox;
use MoonShine\Fields\Code;
use MoonShine\Fields\Color;
use MoonShine\Fields\Date;
use MoonShine\Fields\DateRange;
use MoonShine\Fields\Email;
use MoonShine\Fields\Enum;
use MoonShine\Fields\File;
use MoonShine\Fields\Hidden;
use MoonShine\Fields\ID;
use MoonShine\Fields\Image;
use MoonShine\Fields\Json;
use MoonShine\Fields\Number;
use MoonShine\Fields\Password;
use MoonShine\Fields\Phone;
use MoonShine\Fields\Range;
use MoonShine\Fields\RangeSlider;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Select;
use MoonShine\Fields\Slug;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\Fields\Textarea;
use MoonShine\Fields\TinyMce;
use MoonShine\Fields\Url;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;

final class TypeMap
{
    public function fieldMigrationMap(): array
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
                'bool'
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

    /**
     * @throws ProjectBuilderException
     */
    public function fieldClassFromAlias(string $field): string
    {
        return match ($field) {
            'Text' => Text::class,
            'Hidden' => Hidden::class,
            'ID' => ID::class,
            'Slug' => Slug::class,
            'Color' => Color::class,
            'Url' => Url::class,
            'Email' => Email::class,
            'Phone' => Phone::class,
            'Password' => Password::class,
            'Number' => Number::class,
            'Range' => Range::class,
            'RangeSlider' => RangeSlider::class,
            'Date' => Date::class,
            'DateRange' => DateRange::class,
            'Textarea' => Textarea::class,
            'Code' => Code::class,
            'TinyMce' => TinyMce::class,
            'Select' => Select::class,
            'Enum' => Enum::class,
            'Checkbox' => Checkbox::class,
            'Switcher' => Switcher::class,
            'File' => File::class,
            'Image' => Image::class,
            'Json' => Json::class,
            default => throw new ProjectBuilderException("Field: $field not found")
        };
    }
}