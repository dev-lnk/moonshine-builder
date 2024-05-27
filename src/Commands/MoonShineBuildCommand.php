<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Commands\LaravelCodeBuildCommand;
use DevLnk\LaravelCodeBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\LaravelCodeBuilder\Services\CodePath\CodePathContract;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\CodeStructure;
use DevLnk\LaravelCodeBuilder\Services\CodeStructure\Factories\CodeStructureFromMysql;
use DevLnk\LaravelCodeBuilder\Services\StubBuilder;
use DevLnk\MoonShineBuilder\Enums\MoonShineBuildType;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShineCodePath;
use DevLnk\MoonShineBuilder\Structures\Factories\MoonShineStructureFactory;
use DevLnk\MoonShineBuilder\Traits\CommandVariables;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use SplFileInfo;

use function Laravel\Prompts\{note, select};

class MoonShineBuildCommand extends LaravelCodeBuildCommand
{
    use CommandVariables;

    protected $signature = 'moonshine:build {target?} {--type=} {--model} {--resource} {--migration} {--builders}';

    protected int $iterations = 0;

    /**
     * @var array<int, string>
     */
    protected array $reminderResourceInfo = [];

    /**
     * @var array<int, string>
     */
    protected array $reminderMenuInfo = [];

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

        $this->components->warn("Don't forget to register new resources in the provider method:");
        $code = implode(PHP_EOL, $this->reminderResourceInfo);
        note($code);

        note("...or in the menu method:");

        $code = implode(PHP_EOL, $this->reminderMenuInfo);
        note($code);
        $this->components->info('All done');

        return self::SUCCESS;
    }

    /**
     * @throws CodeGenerateCommandException
     * @throws FileNotFoundException
     */
    protected function buildCode(CodeStructure $codeStructure, CodePathContract $codePath): void
    {
        parent::buildCode($codeStructure, $codePath);

        $resourcePath = $codePath->path(MoonShineBuildType::RESOURCE->value);

        $this->reminderResourceInfo[] = "new {$resourcePath->rawName()}(),";
        $this->reminderMenuInfo[] = StubBuilder::make($this->stubDir . 'MenuItem')
            ->getFromStub([
                '{resource}' => $resourcePath->rawName()
            ])
        ;
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
            $this->builders = array_filter($this->builders, fn($item) => $item !== MoonShineBuildType::MIGRATION);
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

    protected function codePath(): CodePathContract
    {
        $codePath = new MoonShineCodePath($this->iterations);
        $this->iterations++;
        return $codePath;
    }
}
