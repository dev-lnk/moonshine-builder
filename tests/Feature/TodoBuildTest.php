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

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\UI\Fields\Select;",
            "use MoonShine\UI\Fields\Date;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsTo;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsToMany;",
            "use MoonShine\Laravel\Fields\Relationships\HasMany;",
            "ID::make('id')\n\t\t\t\t->sortable()",
            "Text::make('Название', 'title')",
            "Text::make('Описание', 'content')",
            "Select::make('Приоритет', 'priority')\n\t\t\t\t->default('Низкий')\n\t\t\t\t->options(['Низкий', 'Средний', 'Высокий'])",
            "Date::make('Дедлайн', 'deadline')",
            "BelongsTo::make('Ответственный', 'moonshineUser', resource: MoonShineUserResource::class)",
            "BelongsTo::make('Стадия', 'stage', resource: StageResource::class)",
            "BelongsToMany::make('Теги', 'tags', resource: TagResource::class)",
            "HasMany::make('Вложения', 'taskAttachments', resource: TaskAttachmentResource::class)->creatable()",
        ];

        $indexPage = $this->resourcePath . 'Task/Pages/TaskIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Task\TaskResource;",
            "@extends IndexPage<TaskResource>",
        ]);

        $formPage = $this->resourcePath . 'Task/Pages/TaskFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Task\TaskResource;",
            "@extends FormPage<TaskResource>",
            "'title' => ['string', 'required']",
            "'content' => ['string', 'required']",
            "'priority' => ['string', 'required']",
            "'deadline' => ['string', 'required']",
            "'moonshine_user_id' => ['int', 'required']",
            "'stage_id' => ['int', 'required']",
            "'tags' => ['array', 'nullable']",
        ]);

        $detailPage = $this->resourcePath . 'Task/Pages/TaskDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Task\TaskResource;",
            "@extends DetailPage<TaskResource>",
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function taskAttachment(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentIndexPage;",
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentFormPage;",
            "use App\MoonShine\Resources\TaskAttachment\Pages\TaskAttachmentDetailPage;",
            "@extends ModelResource<TaskAttachment, TaskAttachmentIndexPage, TaskAttachmentFormPage, TaskAttachmentDetailPage>",
            "protected string \$model = TaskAttachment::class;",
            "protected string \$column = 'attachment';",
            "protected array \$with = ['task'];",
            "protected string \$title = 'Вложения';",
        ]);

        $this->testBuildFile($modelPath, [
            "class TaskAttachment extends Model",
            "'attachment' => 'json',",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_task_attachments');
        $this->testBuildFile($migrationFile, [
            "\$table->string('attachment');",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsTo;",
            "use MoonShine\UI\Fields\File;",
            "ID::make('id')",
            "BelongsTo::make('Задача', 'task', resource: TaskResource::class)",
            "File::make('Файл', 'attachment')\n\t\t\t\t->multiple()",
        ];

        $indexPage = $this->resourcePath . 'TaskAttachment/Pages/TaskAttachmentIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\TaskAttachment\TaskAttachmentResource;",
            "@extends IndexPage<TaskAttachmentResource>",
        ]);

        $formPage = $this->resourcePath . 'TaskAttachment/Pages/TaskAttachmentFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\TaskAttachment\TaskAttachmentResource;",
            "@extends FormPage<TaskAttachmentResource>",
            "'task_id' => ['int', 'required']",
            "'attachment' => ['array', 'required']",
        ]);

        $detailPage = $this->resourcePath . 'TaskAttachment/Pages/TaskAttachmentDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\TaskAttachment\TaskAttachmentResource;",
            "@extends DetailPage<TaskAttachmentResource>",
        ]);
    }

    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->resourcePath);

        $this->filesystem->delete($this->modelPath . 'Task.php');
        $this->filesystem->delete($this->modelPath . 'TaskTagPivot.php');
        $this->filesystem->delete($this->modelPath . 'TaskAttachment.php');
        $this->filesystem->delete($this->modelPath . 'Stage.php');
        $this->filesystem->delete($this->modelPath . 'Tag.php');

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
