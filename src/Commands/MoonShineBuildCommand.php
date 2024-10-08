<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\LaravelCodeBuilder\Commands\LaravelCodeBuildCommand;
use DevLnk\LaravelCodeBuilder\Enums\BuildTypeContract;
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
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

use function Laravel\Prompts\{confirm, note, select};

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

    protected function buildCode(CodeStructure $codeStructure, CodePathContract $codePath): void
    {
        $buildFactory = $this->buildFactory(
            $codeStructure,
            $codePath,
        );

        $validBuildMap = [
            'withModel' => MoonShineBuildType::MODEL,
            'withMigration' => MoonShineBuildType::MIGRATION,
            'withResource' => MoonShineBuildType::RESOURCE,
        ];

        $validBuilders = [];
        foreach ($validBuildMap as $dataKey => $builder) {
            if (
                ! is_null($codeStructure->dataValue($dataKey))
                && $codeStructure->dataValue($dataKey) === false
            ) {
                continue;
            }
            $validBuilders[] = $builder;
        }

        foreach ($this->builders as $builder) {
            if (! $builder instanceof BuildTypeContract) {
                throw new CodeGenerateCommandException('builder is not DevLnk\LaravelCodeBuilder\Enums\BuildTypeContract');
            }

            if (! in_array($builder, $validBuilders)) {
                continue;
            }

            $confirmed = true;
            if (isset($this->replaceCautions[$builder->value()])) {
                $confirmed = confirm($this->replaceCautions[$builder->value()]);
            }

            if (! $confirmed) {
                continue;
            }

            $buildFactory->call($builder->value(), $this->stubDir . $builder->stub());
            $filePath = $codePath->path($builder->value())->file();
            $this->info($this->projectFileName($filePath) . ' was created successfully!');
        }

        if (! in_array(MoonShineBuildType::RESOURCE, $this->builders)) {
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

        // If it is a sql table, the standard parent package generation is used
        if ($type === 'table') {
            $target = select(
                'Table',
                collect(Schema::getTables())
                    ->filter(fn ($v) => str_contains((string) $v['name'], (string) $target ?? ''))
                    ->mapWithKeys(fn ($v) => [$v['name'] => $v['name']]),
                default: 'jobs'
            );

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
        if (! in_array(MoonShineBuildType::RESOURCE, $this->builders)) {
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
