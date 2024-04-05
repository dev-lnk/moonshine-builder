<?php

namespace MoonShine\ProjectBuilder\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MoonShine\Commands\MoonShineCommand;
use MoonShine\ProjectBuilder\Actions\FieldsToMigration;
use MoonShine\ProjectBuilder\Structures\Factories\StructureFactory;
use MoonShine\ProjectBuilder\Structures\Factories\StructureFromJson;
use MoonShine\ProjectBuilder\Structures\MainStructure;
use MoonShine\ProjectBuilder\Structures\ResourceStructure;
use MoonShine\ProjectBuilder\Exceptions\ProjectBuilderException;

class ProjectBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build';

    protected string $stubsDir = __DIR__ . '/../../stubs';

    /**
     * @throws FileNotFoundException
     * @throws ProjectBuilderException
     */
    public function handle(): void
    {
        $path = base_path('builds/post.json');

        $builder = StructureFactory::make()->getBuilderFromJson($path);

        foreach ($builder->resources() as $resource) {
            $this->createMigration($resource);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function createMigration(ResourceStructure $resourceStructure): void
    {
        $table = $resourceStructure->pluralName();

        $path = base_path('database/migrations/'.date('Y_m_d_His').'_create_'.$table.'.php');

        $columns = $resourceStructure->fieldsToMigration();

        $this->copyStub('Migration', $path, [
            '{{ table }}' => $table,
            '{{ columns }}' => $columns,
        ]);
    }
}
