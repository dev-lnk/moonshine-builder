<?php

namespace DevLnk\MoonShineBuilder\Enums;

use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;

enum LaravelSqlType: string
{
    /*ID*/
    case ID = 'id';

    case BIG_INCREMENTS = 'bigIncrements';

    case MEDIUM_INCREMENTS = 'mediumIncrements';

    case INCREMENTS = 'increments';

    case SMALL_INCREMENTS = 'smallIncrements';

    case TINY_INCREMENTS = 'tinyIncrements';

    /*Number*/
    case BIG_INTEGER = 'bigInteger';

    case MEDIUM_INTEGER = 'mediumInteger';

    case INTEGER = 'integer';

    case SMALL_INTEGER = 'smallInteger';

    case TINY_INTEGER = 'tinyInteger';

    case UNSIGNED_BIG_INTEGER = 'unsignedBigInteger';

    case UNSIGNED_MEDIUM_INTEGER = 'unsignedMediumInteger';

    case UNSIGNED_INTEGER = 'unsignedInteger';

    case UNSIGNED_SMALL_INTEGER = 'unsignedSmallInteger';

    case UNSIGNED_TINY_INTEGER = 'unsignedTinyInteger';

    case DECIMAL = 'decimal';

    case DOUBLE = 'double';

    case FLOAT = 'float';

    /*Switcher*/
    case BOOLEAN = 'boolean';

    /*Text*/
    case CHAR = 'char';

    case STRING = 'string';

    case TEXT = 'text';

    case JSON = 'json';

    case JSONB = 'jsonb';

    case LONG_TEXT = 'longText';

    case MEDIUM_TEXT = 'mediumText';

    case TINY_TEXT = 'tinyText';

    case UUID = 'uuid';

    /*Date*/
    case TIMESTAMP = 'timestamp';

    case DATE_TIME = 'dateTime';

    case DATE = 'date';

    case DATE_TIME_TZ = 'dateTimeTz';

    case YEAR = 'year';

    /*Enum*/
    case ENUM = 'enum';

    /*Relations*/
    case HAS_ONE = 'HasOne';

    case HAS_MANY = 'HasMany';

    case BELONGS_TO = 'BelongsTo';

    public function getMoonShineField(): string
    {
        return match ($this) {
            /*ID*/
            self::ID,
            self::BIG_INCREMENTS,
            self::MEDIUM_INCREMENTS,
            self::INCREMENTS,
            self::SMALL_INCREMENTS,
            self::TINY_INCREMENTS
                => ID::class,

            /*Number*/
            self::BIG_INTEGER,
            self::MEDIUM_INTEGER,
            self::INTEGER,
            self::SMALL_INTEGER,
            self::TINY_INTEGER,
            self::UNSIGNED_BIG_INTEGER,
            self::UNSIGNED_MEDIUM_INTEGER,
            self::UNSIGNED_INTEGER,
            self::UNSIGNED_SMALL_INTEGER,
            self::UNSIGNED_TINY_INTEGER,
            self::DECIMAL,
            self::DOUBLE,
            self::FLOAT,
                => Number::class,

            /*Switcher*/
            self::BOOLEAN => Switcher::class,

            /*Text*/
            self::CHAR,
            self::STRING,
            self::TEXT,
            self::JSON,
            self::JSONB,
            self::LONG_TEXT,
            self::MEDIUM_TEXT,
            self::TINY_TEXT,
            self::UUID,
                => Text::class,

            /*Date*/
            self::TIMESTAMP,
            self::DATE_TIME,
            self::DATE,
            self::DATE_TIME_TZ,
            self::YEAR
                => Date::class,

            /*Enum*/
            self::ENUM => Enum::class,

            /*Relations*/
            self::HAS_ONE => HasOne::class,
            self::HAS_MANY => HasMany::class,
            self::BELONGS_TO => BelongsTo::class,
        };
    }

    public function isIdType(): bool
    {
        $idFields = [
            self::ID,
            self::BIG_INCREMENTS,
            self::MEDIUM_INCREMENTS,
            self::INCREMENTS,
            self::SMALL_INCREMENTS,
            self::TINY_INCREMENTS
        ];

        return in_array($this, $idFields);
    }
}
