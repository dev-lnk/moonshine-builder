<?php

declare(strict_types=1);

namespace MoonShine\ProjectBuilder\Support;

use Illuminate\Support\Facades\File;
use MoonShine\Fields\ID;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Relationships\HasOne;
use MoonShine\Fields\Text;
use MoonShine\MoonShine;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;
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
        return $this->fieldClasses[$field] ?? throw new ProjectBuilderException("Field: $field not found");
    }
}