<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Support;

use DevLnk\LaravelCodeBuilder\Enums\SqlTypeMap;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use Illuminate\Support\Facades\File;
use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\BelongsToMany;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\MoonShine;
use Symfony\Component\Finder\SplFileInfo;

final class TypeMap
{
    /**
     * @var array<string, string>
     */
    private array $fieldClasses;

    public function __construct()
    {
        $this->fieldClasses = collect(File::files(MoonShine::path('src/Fields')))
            ->mapWithKeys(
                fn (SplFileInfo $file): array => [
                    $file->getFilenameWithoutExtension() => 'MoonShine\\Fields\\' . $file->getFilenameWithoutExtension(),
                ]
            )
            ->except(['Field', 'Fields', 'FormElement', 'FormElements'])
            ->toArray()
        ;
    }

    /**
     * @throws ProjectBuilderException
     */
    public function fieldClassFromAlias(string $field): string
    {
        return $this->fieldClasses[$field] ?? throw new ProjectBuilderException("Field: $field not found");
    }

    public function getMoonShineFieldFromSqlType(SqlTypeMap $sqlType): string
    {
        return match ($sqlType) {
            /*ID*/
            SqlTypeMap::ID,
            SqlTypeMap::BIG_INCREMENTS,
            SqlTypeMap::MEDIUM_INCREMENTS,
            SqlTypeMap::INCREMENTS,
            SqlTypeMap::SMALL_INCREMENTS,
            SqlTypeMap::TINY_INCREMENTS
            => ID::class,

            /*Number*/
            SqlTypeMap::BIG_INTEGER,
            SqlTypeMap::MEDIUM_INTEGER,
            SqlTypeMap::INTEGER,
            SqlTypeMap::SMALL_INTEGER,
            SqlTypeMap::TINY_INTEGER,
            SqlTypeMap::UNSIGNED_BIG_INTEGER,
            SqlTypeMap::UNSIGNED_MEDIUM_INTEGER,
            SqlTypeMap::UNSIGNED_INTEGER,
            SqlTypeMap::UNSIGNED_SMALL_INTEGER,
            SqlTypeMap::UNSIGNED_TINY_INTEGER,
            SqlTypeMap::DECIMAL,
            SqlTypeMap::DOUBLE,
            SqlTypeMap::FLOAT,
            => Number::class,

            /*Switcher*/
            SqlTypeMap::BOOLEAN => Switcher::class,

            /*Text*/
            SqlTypeMap::CHAR,
            SqlTypeMap::STRING,
            SqlTypeMap::TEXT,
            SqlTypeMap::JSON,
            SqlTypeMap::JSONB,
            SqlTypeMap::LONG_TEXT,
            SqlTypeMap::MEDIUM_TEXT,
            SqlTypeMap::TINY_TEXT,
            SqlTypeMap::UUID,
            => Text::class,

            /*Date*/
            SqlTypeMap::TIMESTAMP,
            SqlTypeMap::TIME,
            SqlTypeMap::DATE_TIME,
            SqlTypeMap::DATE,
            SqlTypeMap::DATE_TIME_TZ,
            SqlTypeMap::YEAR
            => Date::class,

            /*Enum*/
            SqlTypeMap::ENUM => Enum::class,

            /*Relations*/
            SqlTypeMap::HAS_ONE => HasOne::class,
            SqlTypeMap::HAS_MANY => HasMany::class,
            SqlTypeMap::BELONGS_TO => BelongsTo::class,
            SqlTypeMap::BELONGS_TO_MANY => BelongsToMany::class,
        };
    }
}
