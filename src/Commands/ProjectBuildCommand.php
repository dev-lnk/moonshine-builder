<?php

namespace MoonShine\ProjectBuilder\Commands;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use MoonShine\Commands\MoonShineCommand;
use MoonShine\ProjectBuilder\Actions\FieldsToMigration;

class ProjectBuildCommand extends MoonShineCommand
{
    protected $signature = 'moonshine:build';

    protected string $stubsDir = __DIR__ . '/../../stubs';

    /**
     * @throws FileNotFoundException
     */
    public function handle(): void
    {
        $path = base_path('builds/post.json');

        $project = json_decode(file_get_contents($path), true);

        foreach ($project['resources'] as $resources) {
            foreach ($resources as $resource => $values) {
                $name = str($resource)->replace('Resource', '')->value();
                $lowName = str($name)->snake()->lower()->value();
                $pluralName = str($lowName)->plural()->value();

                $this->createMigration($pluralName, $values['fields']);
            }
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function createMigration(string $table, array $fields): void
    {
        $path = base_path('database/migrations/'.date('Y_m_d_His').'_create_'.$table.'.php');

        $columns = FieldsToMigration::make()->handle($fields);

        $this->copyStub('Migration', $path, [
            '{{ table }}' => $table,
            '{{ columns }}' => $columns,
        ]);
    }
}
