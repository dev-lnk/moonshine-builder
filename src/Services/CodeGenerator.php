<?php

namespace DevLnk\MoonShineBuilder\Services;

use DevLnk\MoonShineBuilder\Enums\BuildType;
use DevLnk\MoonShineBuilder\Enums\BuildTypeContract;
use DevLnk\MoonShineBuilder\Exceptions\CodeGenerateCommandException;
use DevLnk\MoonShineBuilder\Exceptions\NotFoundBuilderException;
use DevLnk\MoonShineBuilder\Services\Builders\Factory\MoonShineBuildFactory;
use DevLnk\MoonShineBuilder\Services\CodePath\CodePathContract;
use DevLnk\MoonShineBuilder\Services\CodePath\MoonShineCodePath;
use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MoonShine\Laravel\Commands\MoonShineCommand;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\{confirm, note};

class CodeGenerator
{
    private MoonShineCommand $command;

    private int $iterations = 0;

    private ?string $stubDir = '';

    /** @var list<string> */
    private array $reminderResourceInfo = [];

    /** @var list<string> */
    private array $reminderMenuInfo = [];

    /** @var list<array<array-key, string>> */
    private array $resourceInfo = [];

    /** @var list<BuildType> */
    private array $builders = [];

    /** @var array<string, string> */
    private array $replaceCautions = [];

    public function __construct() {
        $this->setStubDir();
        $this->prepareBuilders();
    }

    public function setCommand(MoonShineCommand $command): void
    {
        $this->command = $command;
    }

    /**
     * @throws CodeGenerateCommandException
     * @throws FileNotFoundException
     * @throws NotFoundBuilderException
     */
    public function make(CodeStructure $codeStructure, ?string $generationPath = null): void
    {
        $codeStructure->setStubDir($this->stubDir);

        $codePath = $this->codePath();

        $this->prepareGeneration($codeStructure, $codePath, $generationPath);

        $this->buildCode($codeStructure, $codePath);
    }

    /**
     * @throws CodeGenerateCommandException
     * @throws FileNotFoundException
     * @throws NotFoundBuilderException
     */
    private function buildCode(CodeStructure $codeStructure, CodePathContract $codePath): void
    {
        $buildFactory = new MoonShineBuildFactory(
            $codeStructure,
            $codePath
        );

        $validBuilders = array_filter([
            $codeStructure->withModel() ? BuildType::MODEL : null,
            $codeStructure->withMigration() ? BuildType::MIGRATION : null,
            $codeStructure->withResource() ? BuildType::RESOURCE : null,
            BuildType::INDEX_PAGE,
            BuildType::FORM_PAGE,
            BuildType::DETAIL_PAGE,
        ]);

        foreach ($this->builders as $builder) {
            if(! $builder instanceof BuildTypeContract) {
                throw new CodeGenerateCommandException('builder is not BuildTypeContract');
            }

            if(! in_array($builder, $validBuilders)) {
                continue;
            }

            $confirmed = true;
            if(
                config('moonshine_builder.is_confirm_replace_files', true)
                && isset($this->replaceCautions[$builder->value()])
            ) {
                $confirmed = confirm($this->replaceCautions[$builder->value()]);
            }

            if(! $confirmed) {
                continue;
            }

            $buildFactory->call($builder->value(), $this->stubDir . $builder->stub());
            $filePath = $codePath->path($builder->value())->file();
            $this->command->info($this->projectFileName($filePath) . ' was created successfully!');
        }

        if(! in_array(BuildType::RESOURCE, $this->builders)) {
            return;
        }

        if($codeStructure->withResource()) {
            $resourcePath = $codePath->path(BuildType::RESOURCE->value);

            $this->reminderResourceInfo[] = "{$resourcePath->rawName()}::class,";

            $entityName = str_replace('Resource', '', $resourcePath->rawName());

            $this->reminderMenuInfo[] = StubBuilder::make($this->stubDir . 'MenuItem')
                ->getFromStub([
                    '{menuName}' => $codeStructure->menuName(),
                    '{resource}' => '\\App\\MoonShine\\Resources\\' . $entityName . '\\' . $resourcePath->rawName(),
                ])
            ;

            $this->resourceInfo[] = [
                'className' => $resourcePath->rawName(),
                'menuName' => $codeStructure->menuName(),
                'namespace' => 'App\\MoonShine\\Resources\\' . $entityName . '\\',
            ];
        }
    }

    private function prepareGeneration(CodeStructure $codeStructure, CodePathContract $codePath, ?string $generationPath): void
    {
        $isGenerationDir = $generationPath !== null;

        $fileSystem = new Filesystem();

        if($isGenerationDir) {
            $genPath = base_path($generationPath);
            if(! $fileSystem->isDirectory($genPath)) {
                $fileSystem->makeDirectory($genPath, recursive: true);
                $fileSystem->put($genPath . '/.gitignore', "*\n!.gitignore");
            }
        }

        $codePath->initPaths($codeStructure);

        if(! $isGenerationDir) {
            foreach ($this->builders as $buildType) {
                if($fileSystem->isFile($codePath->path($buildType->value())->file())) {
                    $this->replaceCautions[$buildType->value()] =
                        $this->projectFileName($codePath->path($buildType->value())->file()) . " already exists, are you sure you want to replace it?";
                }
            }
        }
    }

    private function projectFileName(string $filePath): string
    {
        if(str_contains($filePath, '/resources/views')) {
            return substr($filePath, strpos($filePath, '/resources/views') + 1);
        }

        if(str_contains($filePath, '/routes')) {
            return substr($filePath, strpos($filePath, '/routes') + 1);
        }

        if (str_starts_with($filePath, base_path())) {
            return substr($filePath, strlen(base_path()) + 1);
        }

        return substr($filePath, strpos($filePath, '/app') + 1);
    }

    private function codePath(): CodePathContract
    {
        $codePath = new MoonShineCodePath($this->iterations);
        $this->iterations++;

        return $codePath;
    }

    public function resourceInfo(): void
    {
        if(! in_array(BuildType::RESOURCE, $this->builders)) {
            return;
        }

        if (config('moonshine_builder.is_confirm_change_provider') && ! confirm('Add new resources to the provider?')) {
            note("Don't forget to register new resources in the provider method:");
            $code = implode(PHP_EOL, $this->reminderResourceInfo);
            note($code);
        } else {
            foreach ($this->resourceInfo as $info) {
                $this->command::addResourceOrPageToProviderFile($info['className'], namespace: $info['namespace']);
            }
        }

        if (config('moonshine_builder.is_confirm_change_menu') && ! confirm('Add new resources to the menu?')) {
            note("Do not forget to add Resources to the menu:");
            $code = implode(PHP_EOL, $this->reminderMenuInfo);
            note($code);
        } else if (! app()->runningUnitTests()) {
            // TODO Не работает в тестовой среде из-за метода addResourceOrPageToMenu
            // new ReflectionClass(moonshineConfig()->getLayout()) выбрасывает исключение
            foreach ($this->resourceInfo as $info) {
                $this->command::addResourceOrPageToMenu($info['className'], $info['menuName'], $info['namespace']);
            }
        }
    }

    private function setStubDir(): void
    {
        $this->stubDir = __DIR__ . '/../../stubs/';
    }

    private function prepareBuilders(): void
    {
        $this->builders = [
            BuildType::MODEL,
            BuildType::RESOURCE,
            BuildType::MIGRATION,
            BuildType::INDEX_PAGE,
            BuildType::FORM_PAGE,
            BuildType::DETAIL_PAGE,
        ];
    }

    public function getBuilders(): array
    {
        return $this->builders;
    }

    public function replaceBuilder(array $builders): void
    {
        $this->builders = $builders;
    }
}
