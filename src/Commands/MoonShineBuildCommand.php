<?php

namespace DevLnk\MoonShineBuilder\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MoonShine\Commands\MoonShineCommand;
use MoonShine\MoonShine;
use DevLnk\MoonShineBuilder\Structures\Factories\StructureFactory;
use DevLnk\MoonShineBuilder\Structures\ResourceStructure;
use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;

class MoonShineBuildCommand extends MoonShineCommand
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

        $path = $dir . '/' . $this->argument('file');

        $builder = StructureFactory::make()->getBuilderFromJson($path);

        $reminderResourceInfo = [];
        $reminderMenuInfo = [];

        foreach ($builder->resources() as $index => $resource) {
            $this->warn("app/MoonShine/Resources/{$resource->resourceName()} is created...");

            $this->createModel($resource);
            $this->createMigration($resource, $index);
            $this->createResource($resource);

            $this->info("app/MoonShine/Resources/{$resource->resourceName()} created successfully");
            $this->newLine();

            $reminderResourceInfo[] = "new {$resource->resourceName()}(),";
            $reminderMenuInfo[] = $this->replaceInStub('MenuItem', [
                '{resource}' => $resource->name()->ucFirst(),
            ]);
        }

        $this->warn("Don't forget to register new resources in the provider method – resources:");
        $this->info(implode("\n", $reminderResourceInfo));
        $this->warn("...or in the menu method:");
        $this->info(implode("\n", $reminderMenuInfo));

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

        $relationUses = $resourceStructure->relationUses();

        $relationsBlock = '';

        if(! empty($resourceStructure->relationFields())) {
            foreach ($resourceStructure->relationsData() as $relationsData) {
                $relationsBlock .= str($this->replaceInStub($relationsData['stub'], [
                    '{relation}' => $relationsData['relation'],
                    '{relation_model}' => $relationsData['relation_model']
                ]))
                    ->newLine()
                    ->newLine()
                    ->value()
                ;
            }
        }

        $this->copyStub('Model', $path, [
            '{namespace}' => 'App\Models',
            '{class}' => $modelName,
            '{fillable}' => $fillable,
            '{relation_uses}' => $relationUses,
            '{relations_block}' => $relationsBlock,
        ]);

        $this->info("Model App\\Models\\$modelName created successfully");
    }

    /**
     * @throws FileNotFoundException
     */
    private function createMigration(ResourceStructure $resourceStructure, int $index): void
    {
        $table = $resourceStructure->name()->plural();

        // TODO подумать как сделать рефакторинг $index
        $migrationPath = 'database/migrations/'.date('Y_m_d_His').'_'.$index.'_create_'.$table.'.php';

        $path = base_path($migrationPath);

        $columns = $resourceStructure->fieldsToMigration();

        $this->copyStub('Migration', $path, [
            '{table}' => $table,
            '{columns}' => $columns,
        ]);

        $this->info("Migration $migrationPath created successfully");
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
            '{column}' => $resourceStructure->columnToResource(),
            '{fields}' => $fields,
            '{model}' => class_basename($model),
            'DummyTitle' => class_basename($model),
            'Dummy' => $resourceStructure->name()->ucFirst(),
        ]);
    }
}
