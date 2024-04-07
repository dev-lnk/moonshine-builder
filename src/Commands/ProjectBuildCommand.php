<?php

namespace MoonShine\ProjectBuilder\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MoonShine\Commands\MoonShineCommand;
use MoonShine\MoonShine;
use MoonShine\ProjectBuilder\Structures\Factories\StructureFactory;
use MoonShine\ProjectBuilder\Structures\ResourceStructure;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;

class ProjectBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build {file}';

    protected string $stubsDir = __DIR__ . '/../../stubs';

    /**
     * @throws FileNotFoundException
     * @throws ProjectBuilderException
     */
    public function handle(): int
    {
        if(! $this->hasArgument('file')) {
            return self::FAILURE;
        }

        $dir = config('moonshine_builder.builds_dir');

        $path = $dir . '/' . $this->argument('file') . '.json';

        $builder = StructureFactory::make()->getBuilderFromJson($path);

        foreach ($builder->resources() as $resource) {
            $this->createModel($resource);
            $this->createMigration($resource);
            $this->createResource($resource);
        }

        return self::SUCCESS;
    }

    /**
     * @throws FileNotFoundException
     */
    private function createModel(ResourceStructure $resourceStructure): void
    {
        $modelName = $resourceStructure->name()->ucFirst();

        $path = base_path("app/Models/$modelName.php");

        $fillable = $resourceStructure->fieldsToModel();

        $this->copyStub('Model', $path, [
            '{namespace}' => 'App\Models',
            '{class}' => $modelName,
            '{fillable}' => $fillable
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createMigration(ResourceStructure $resourceStructure): void
    {
        $table = $resourceStructure->name()->plural();

        $path = base_path('database/migrations/'.date('Y_m_d_His').'_create_'.$table.'.php');

        $columns = $resourceStructure->fieldsToMigration();

        $this->copyStub('Migration', $path, [
            '{table}' => $table,
            '{columns}' => $columns,
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function createResource(ResourceStructure $resourceStructure): void
    {
        $name = $resourceStructure->resourceName();

        $model = $this->qualifyModel($resourceStructure->name()->ucFirst());

        $path = base_path("app/MoonShine/Resources/$name.php");

        $fieldsUses = $resourceStructure->usesFieldsToResource();

        $fields = $resourceStructure->fieldsToResources();

        $this->copyStub('ModelResourceDefault', $path, [
            '{namespace}' => MoonShine::namespace('\Resources'),
            '{model-namespace}' => $model,
            '{uses}' => $fieldsUses,
            '{fields}' => $fields,
            '{model}' => class_basename($model),
            'DummyTitle' => class_basename($model),
            'Dummy' => $resourceStructure->name()->ucFirst(),
        ]);
    }
}
