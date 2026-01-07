<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromModel;
use Illuminate\Support\Facades\File;
use function Laravel\Prompts\{multiselect, text};
use SplFileInfo;

class ModelBuildCommand extends AbstractBuildCommand
{
    protected $signature = 'moonshine:build-model {entity?} {--all : Process all models from the models directory}';

    public function handle(): int
    {
        $this->init();

        $entity = $this->argument('entity');
        $all = $this->option('all');

        $entities = $this->resolveEntities($entity, $all);

        $processedCount = 0;

        foreach ($entities as $entity) {
            $modelClass = $this->resolveModelClass($entity);

            $this->components->info("Processing model: <fg=yellow>{$modelClass}</>");

            $codeStructureList = (new StructureFromModel($modelClass))->makeStructures();

            $this->make($codeStructureList->codeStructures()[0], $this->generationPath);

            $processedCount++;
        }

        $this->components->info("Processed {$processedCount} model(s) successfully");

        $this->resourceInfo();

        return self::SUCCESS;
    }

    /**
     * @return array<int, string>
     */
    private function resolveEntities(?string $entity, bool $all): array
    {
        if ($all) {
            return $this->getAllModels();
        }

        if ($entity !== null) {
            return [$entity];
        }

        return $this->selectModels();
    }

    /**
     * @return array<int, string>
     */
    private function getAllModels(): array
    {
        $models = $this->scanModels();

        if (empty($models)) {
            $baseModelPath = config('moonshine_builder.base_model_path', 'app/Models');
            $this->components->warn("No models found in {$baseModelPath} directory.");
            
            return [];
        }

        return array_keys($models);
    }

    /**
     * @return array<int, string>
     */
    private function selectModels(): array
    {
        $models = $this->scanModels();

        if (empty($models)) {
            $baseModelPath = config('moonshine_builder.base_model_path', 'app/Models');
            $this->components->warn("No models found in {$baseModelPath} directory.");
            
            return [text('Enter model name manually:')];
        }

        $modelOptions = [];
        foreach ($models as $modelClass => $modelPath) {
            $modelOptions[$modelClass] = $modelPath;
        }

        $selectedModels = multiselect(
            label: 'Select models (use Space to select, Enter to confirm):',
            options: $modelOptions,
            scroll: 10,
            required: true
        );

        return $selectedModels;
    }

    /**
     * @return array<string, string>
     */
    private function scanModels(): array
    {
        $baseModelPath = config('moonshine_builder.base_model_path', 'app/Models');
        $modelsPath = base_path($baseModelPath);
        
        if (!is_dir($modelsPath)) {
            return [];
        }

        $models = [];
        $files = File::allFiles($modelsPath);

        foreach ($files as $file) {
            if ($file->getExtension() !== 'php') {
                continue;
            }

            $modelClass = $this->getClassNameFromFile($file, $modelsPath);
            
            if (class_exists($modelClass)) {
                $relativePath = str_replace(base_path() . DIRECTORY_SEPARATOR, '', $file->getPathname());
                $models[$modelClass] = $relativePath;
            }
        }

        ksort($models);

        return $models;
    }

    private function getClassNameFromFile(SplFileInfo $file, string $basePath): string
    {
        $baseModelPath = config('moonshine_builder.base_model_path', 'app/Models');
        $namespace = str_replace('/', '\\', ucfirst($baseModelPath));
        
        $relativePath = str_replace($basePath . DIRECTORY_SEPARATOR, '', $file->getPathname());
        $relativePath = str_replace('.php', '', $relativePath);
        $relativePath = str_replace(DIRECTORY_SEPARATOR, '\\', $relativePath);

        return $namespace . '\\' . $relativePath;
    }

    private function resolveModelClass(string $entity): string
    {
        if (class_exists($entity)) {
            return $entity;
        }

        if (str_contains($entity, '\\')) {
            return $entity;
        }

        $baseModelPath = config('moonshine_builder.base_model_path', 'app/Models');
        $namespace = str_replace('/', '\\', ucfirst($baseModelPath));
        
        $entityWithNamespace = str_replace('/', '\\', $entity);
        
        return $namespace . '\\' . $entityWithNamespace;
    }
}