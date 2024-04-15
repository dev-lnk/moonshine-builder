<?php

namespace DevLnk\MoonShineBuilder\Commands;

use DevLnk\MoonShineBuilder\Exceptions\ProjectBuilderException;
use DevLnk\MoonShineBuilder\Structures\Factories\StructureFactory;
use DevLnk\MoonShineBuilder\Structures\ResourceStructure;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use MoonShine\Commands\MoonShineCommand;
use MoonShine\MoonShine;
use SplFileInfo;

use function Laravel\Prompts\{select, note, confirm};

class MoonShineBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build {target?} {--type=}';

    protected string $stubsDir = __DIR__ . '/../../stubs';

    /**
     * @throws FileNotFoundException
     * @throws ProjectBuilderException
     */
    public function handle(): int
    {
        $target = $this->argument('target');
        $type = $this->option('type') ?? select('Type', ['json', 'table']);
        $withModel = false;

        if (is_null($target) && $type === 'json') {
            $target = select(
                'File',
                collect(File::files(config('moonshine_builder.builds_dir')))->mapWithKeys(
                    fn (SplFileInfo $file): array => [
                        $file->getFilename() => $file->getFilename(),
                    ]
                ),
            );

            $withModel = true;
        }

        if (is_null($target) && $type === 'table') {
            $target = select(
                'Table',
                collect(Schema::getTables())->map(fn ($v) => $v['name']),
            );

            $withModel = confirm('Make model?', default: false, hint: 'If the model exists, it will be overwritten');
        }

        $mainStructure = StructureFactory::make()
            ->getStructure($target, $type);

        $mainStructure->setWithModel($withModel);

        $reminderResourceInfo = [];
        $reminderMenuInfo = [];

        foreach ($mainStructure->resources() as $index => $resource) {
            $this->components->task("app/MoonShine/Resources/{$resource->resourceName()} is created...");

            if ($mainStructure->withModel()) {
                $this->createModel($resource);
            }

            if ($mainStructure->withMigration()) {
                $this->createMigration($resource, $index);
            }

            if ($mainStructure->withResource()) {
                $this->createResource($resource);
                $reminderResourceInfo[] = "new {$resource->resourceName()}(),";
                $reminderMenuInfo[] = $this->replaceInStub('MenuItem', [
                    '{resource}' => $resource->name()->ucFirst(),
                ]);
            }
        }

        $this->components->warn("Don't forget to register new resources in the provider method:");

        $code = implode(PHP_EOL, $reminderResourceInfo);

        note($code);

        $this->components->warn("...or in the menu method:");

        $code = implode(PHP_EOL, $reminderMenuInfo);

        note($code);

        $this->components->info('All done');

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

        if (! empty($resourceStructure->relationFields())) {
            foreach ($resourceStructure->relationsData() as $relationsData) {
                $relationsBlock .= str($this->replaceInStub($relationsData['stub'], [
                    '{relation}' => $relationsData['relation'],
                    '{relation_model}' => $relationsData['relation_model'],
                ]))
                    ->newLine()
                    ->newLine()
                    ->value();
            }
        }

        $this->copyStub('Model', $path, [
            '{namespace}' => 'App\Models',
            '{class}' => $modelName,
            '{fillable}' => $fillable,
            '{relation_uses}' => $relationUses,
            '{relations_block}' => $relationsBlock,
        ]);

        $this->components->task("Model App\\Models\\$modelName created successfully");
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

        $this->copyStub('Migration', $path, [
            '{table}' => $table,
            '{columns}' => $columns,
        ]);

        $this->components->task("Migration $migrationPath created successfully");
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

        $this->components->task("app/MoonShine/Resources/{$resourceStructure->resourceName()} created successfully");
    }
}
