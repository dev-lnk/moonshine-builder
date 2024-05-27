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

use function Laravel\Prompts\{note, select};

use SplFileInfo;

class MoonShineBuildCommand extends LaravelCodeBuildCommand
{
    use CommandVariables;

    protected $signature = 'moonshine:build {target?} {--type=}';

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

        $this->resourceInfo();

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

        if(! in_array(MoonShineBuildType::RESOURCE, $this->builders)) {
            return;
        }

        $resourcePath = $codePath->path(MoonShineBuildType::RESOURCE->value);

        $this->reminderResourceInfo[] = "new {$resourcePath->rawName()}(),";
        $this->reminderMenuInfo[] = StubBuilder::make($this->stubDir . 'MenuItem')
            ->getFromStub([
                '{resource}' => $resourcePath->rawName(),
            ])
        ;
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

        if ($type === 'table') {
            $target = select(
                'Table',
                collect(Schema::getTables())
                    ->filter(fn ($v) => str_contains((string) $v['name'], (string) $target ?? ''))
                    ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]),
                default: 'jobs'
            );
        }

        if($type === 'table') {
            $this->builders = array_filter($this->builders, fn ($item) => $item !== MoonShineBuildType::MIGRATION);

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

        $codeStructures = (new MoonShineStructureFactory())->getStructures($target);

        if(! $codeStructures->withModel()) {
            $this->builders = array_filter($this->builders, fn ($item) => $item !== MoonShineBuildType::MODEL);
        }

        if(! $codeStructures->withMigration()) {
            $this->builders = array_filter($this->builders, fn ($item) => $item !== MoonShineBuildType::MIGRATION);
        }

        if(! $codeStructures->withResource()) {
            $this->builders = array_filter($this->builders, fn ($item) => $item !== MoonShineBuildType::RESOURCE);
        }

        return $codeStructures->codeStructures();
    }

    protected function getType(?string $target): string
    {
        if (! $this->option('type') && ! is_null($target)) {
            $availableTypes = [
                'json',
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

    protected function resourceInfo(): void
    {
        if(! in_array(MoonShineBuildType::RESOURCE, $this->builders)) {
            return;
        }

        $this->components->warn(
            "Don't forget to register new resources in the provider method:"
        );
        $code = implode(PHP_EOL, $this->reminderResourceInfo);
        note($code);

        note("...or in the menu method:");

        $code = implode(PHP_EOL, $this->reminderMenuInfo);
        note($code);
    }
}
