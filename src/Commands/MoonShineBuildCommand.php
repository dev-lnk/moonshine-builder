<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Commands\LaravelCodeBuildCommand;
use DevLnk\LaravelCodeBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\Factories\CodeStructureFromMysql;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\Factories\MoonShineStructureFactory;
use DevLnk\MoonShineBuilder\Structures\ResourceStructure;
use DevLnk\MoonShineBuilder\Traits\CommandVariables;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;

use function Laravel\Prompts\{select};

class MoonShineBuildCommand extends LaravelCodeBuildCommand
{
    use CommandVariables;

    protected $signature = 'moonshine:build {target?} {--type=} {--model} {--resource} {--migration} {--builders}';

    /**
     * @throws CodeGenerateCommandException
     * @throws ProjectBuilderException
     */
    public function handle(): int
    {
        $this->setStubDir();

        $this->prepareBuilders();

        $codeStructures = $this->codeStructures();

        $generationPath = $this->generationPath();

        foreach ($codeStructures as $codeStructure) {
            $this->make($codeStructure, $generationPath);
        }

        return self::SUCCESS;
    }

    protected function prepareBuilders(): void
    {
        $builders = $this->builders();

        foreach ($builders as $builder) {
            if($this->option($builder->value())) {
                $this->builders[] = $builder;
            }
        }

        if($this->option('builders')) {
            foreach (config('code_builder.builders', []) as $builder) {
                if(! in_array($builder, $this->builders)) {
                    $this->builders[] = $builder;
                }
            }
        }

        if(empty($this->builders)) {
            $this->builders = $this->builders();
        }
    }

    /**
     * @return array<int, CodeStructure>
     *
     * @throws ProjectBuilderException
     */
    protected function codeStructures(): array
    {
        $target = $this->argument('target');

        $type = $this->getType($target);

        if (is_null($target) && $type === 'json') {
            $target = select(
                'File',
                collect(File::files(config('moonshine_builder.builds_dir')))->mapWithKeys(
                    fn (SplFileInfo $file): array => [
                        $file->getFilename() => $file->getFilename(),
                    ]
                ),
            );
        }

        if (is_null($target) && $type === 'table') {
            $target = select(
                'Table',
                collect(Schema::getTables())->map(fn ($v) => $v['name']),
            );
        }

        if($type === 'table') {
            return [
                CodeStructureFromMysql::make(
                    table: (string) $target,
                    entity: $target,
                    isBelongsTo: true,
                    hasMany: [],
                    hasOne: [],
                    belongsToMany: []
                ),
            ];
        }

        $codeStructures = MoonShineStructureFactory::make()
            ->getStructures($target);

        if(! $codeStructures->withModel()) {
            $this->builders = array_filter($this->builders, fn($item) => $item !== MoonShineBuildType::MODEL);
        }

        if(! $codeStructures->withMigration()) {
            $this->builders = array_filter($this->builders, fn($item) => $item !== MoonShineBuildType::MIGRATION);
        }

        if(! $codeStructures->withResource()) {
            $this->builders = array_filter($this->builders, fn($item) => $item !== MoonShineBuildType::RESOURCE);
        }

        return $codeStructures->codeStructures();
    }

    protected function getType(?string $target): string
    {
        if (! $this->option('type') && ! is_null($target)) {
            $availableTypes = [
                'json'
            ];

            $fileSeparate = explode('.', $target);
            $type = $fileSeparate[count($fileSeparate) - 1];

            if (in_array($type, $availableTypes)) {
                return $type;
            }
        }

        return $this->option('type') ?? select('Type', ['json', 'table']);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createMigration(ResourceStructure $resourceStructure, int $index): void
    {
        $table = $resourceStructure->name()->plural();

        // TODO подумать как сделать рефакторинг $index
        $migrationPath = 'database/migrations/' . date('Y_m_d_His') . '_' . $index . '_create_' . $table . '.php';

        $path = base_path($migrationPath);

        $columns = $resourceStructure->fieldsToMigration();

        $timestamps = $resourceStructure->isTimestamps()
            ? "\n\t\t\t\$table->timestamps();"
            : '';

        $softDeletes = $resourceStructure->isSoftDeletes()
            ? "\n\t\t\t\$table->softDeletes();"
            : '';

        $this->copyStub('Migration', $path, [
            '{table}' => $table,
            '{columns}' => $columns,
            '{timestamps}' => $timestamps,
            '{soft_deletes}' => $softDeletes,
        ]);

        $this->components->task("Migration $migrationPath created successfully");
    }
}
