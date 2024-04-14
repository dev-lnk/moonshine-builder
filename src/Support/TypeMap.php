<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Support;

use Illuminate\Support\Facades\File;
use MoonShine\Fields\Date;
use MoonShine\Fields\Enum;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Switcher;
use MoonShine\Fields\Text;
use MoonShine\MoonShine;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
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
                    $file->getFilenameWithoutExtension() => 'MoonShine\\Fields\\'.$file->getFilenameWithoutExtension(),
                ]
            )
            ->except(['Field', 'Fields', 'FormElement', 'FormElements'])
            ->toArray()
        ;
    }

    public function fieldMigrationMap(): array
    {
        return [
            ID::class => [
                'id'  => 'PRIMARY',
                'bigIncrements' => 'PRIMARY',
                'mediumIncrements' => 'PRIMARY',
                'increments' => 'PRIMARY',
                'smallIncrements' => 'PRIMARY',
                'tinyIncrements' => 'PRIMARY',
            ],
            Number::class => [
                'bigInteger' => 'BIGINT',
                'mediumInteger' => 'MEDIUMINT',
                'integer' => 'INTEGER',
                'smallInteger' => 'SMALLINT',
                'tinyInteger' => 'TINYINT',
                'unsignedBigInteger' => 'BIGINT',
                'unsignedMediumInteger' => 'MEDIUMINT',
                'unsignedInteger' => 'INTEGER',
                'unsignedSmallInteger' => 'SMALLINT',
                'unsignedTinyInteger' => 'TINYINT',
                'decimal' => 'DECIMAL',
                'double' => 'DOUBLE',
                'float' => 'FLOAT'
            ],
            Switcher::class => [
                'boolean' => 'BOOLEAN'
            ],
            Text::class => [
                'char' => 'CHAR',
                'string' => 'VARCHAR',
                'text' => 'TEXT',
                'json' => 'JSON',
                'jsonb' => 'JSONB',
                'longText' => 'LONGTEXT',
                'mediumText' => 'MEDIUMTEXT',
                'tinyText' => 'TINYTEXT'
            ],
            BelongsTo::class => [
                'BelongsTo' => null
            ],
            HasMany::class => [
                'HasMany' => null
            ],
            HasOne::class => [
                'HasOne' => null
            ],
            Date::class => [
                'timestamp' => 'TIMESTAMP',
                'dateTime' => 'DATETIME',
                'date' => 'DATE',
                'dateTimeTz' => 'DATETIME',
            ],
            Enum::class => [
                'enum' => 'ENUM'
            ]
        ];
    }

    /**
     * @throws ProjectBuilderException
     */
    public function fieldClassFromAlias(string $field): string
    {
        return $this->fieldClasses[$field] ?? throw new ProjectBuilderException("Field: $field not found");
    }
}