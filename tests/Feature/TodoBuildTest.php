<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class TodoBuildTest extends TestCase
{
    private string $resourcePath = '';

    private string $modelPath = '';

    private string $migrationPath = '';

    public function setUp(): void
    {
        parent::setUp();

        $this->filesystem = new Filesystem();

        $this->resourcePath = config('moonshine.dir') . '/Resources/';

        $this->modelPath = app_path('Models/');

        $this->migrationPath = base_path('database/migrations/');
    }

    /**
     * @throws FileNotFoundException
     */
    #[Test]
    public function build(): void
    {
        $this->artisan('moonshine:build todo.json --type=json');

        $this->task($this->resourcePath . 'Task/TaskResource.php', $this->modelPath . 'Task.php');
        $this->taskAttachment($this->resourcePath . 'TaskAttachment/TaskAttachmentResource.php', $this->modelPath . 'TaskAttachment.php');
    }

    /**
     * @throws FileNotFoundException
     */
    private function task(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\MoonShine\Resources\Task\Pages\TaskIndexPage",
            "use App\MoonShine\Resources\Task\Pages\TaskFormPage",
            "use App\MoonShine\Resources\Task\Pages\TaskDetailPage",
            "@extends ModelResource<Task, TaskIndexPage, TaskFormPage, TaskDetailPage>",
            "use App\Models\Task;",
            "protected array \$with = ['moonshineUser', 'stage', 'tags', 'taskAttachments'];",
        ]);

        $this->testBuildFile($modelPath, [
            "class Task extends Model",
            "use SoftDeletes;",
            "return \$this->hasMany(TaskAttachment::class, 'task_id');",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_tasks');
        $this->testBuildFile($migrationFile, [
            "Schema::create('tasks', function (Blueprint \$table) {",
            "\$table->string('priority')->default('Низкий');",
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function taskAttachment(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentIndexPage;",
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentFormPage;",
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentDetailPage;",
            "@extends ModelResource<TaskAttachment, TaskAttachmentIndexPage, TaskAttachmentFormPage, TaskAttachmentDetailPage>",
            "protected string \$model = TaskAttachment::class;",
            "protected string \$column = 'attachment';",
            "protected array \$with = ['task'];",
            "protected string \$title = 'Вложения';",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "class TaskAttachment extends Model",
            "'attachment' => 'json',",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_task_attachments');
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "\$table->string('attachment');",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    public function tearDown(): void
    {
        $this->filesystem->delete($this->resourcePath);

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
