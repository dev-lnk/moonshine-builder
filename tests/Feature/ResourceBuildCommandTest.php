<?php

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use DevLnk\MoonShineBuilder\Tests\Traits\GetMigrationFile;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class ResourceBuildCommandTest extends TestCase
{
    use GetMigrationFile;

    private string $resourcePath = '';

    private string $modelPath = '';

    private string $migrationPath = '';

    private Filesystem $filesystem;

    public function setUp(): void
    {
        parent::setUp();

        $this->resourcePath = config('moonshine.dir') . '/Resources/';

        $this->modelPath = app_path('Models/');

        $this->migrationPath = base_path('database/migrations/');

        $this->filesystem = new Filesystem();
    }

    /**
     * @throws FileNotFoundException
     */
    #[Test]
    public function console(): void
    {
        $this->artisan('ms-build:resource Post id:ID:id name:Name:string status_id:Status:BelongsTo:statuses')
            ->expectsQuestion('Add timestamps?', true)
            ->expectsQuestion('Add softDelete?', true)
            ->expectsQuestion('Make migration?', true)
        ;

        $this->assertPost();
    }

    /**
     * @throws FileNotFoundException
     */
    private function assertPost(): void
    {
        $resourcePath = $this->resourcePath . 'PostResource.php';
        $modelPath = $this->modelPath . 'Post.php';

        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use MoonShine\Fields\ID;",
            "use MoonShine\Fields\Text;",
            "use MoonShine\Fields\Relationships\BelongsTo;",
            "@extends ModelResource<Post>",
            "protected array \$with = ['status'];",
            "BelongsTo::make('Status', 'status', resource: new StatusResource())",
            "'id' => ['int', 'nullable']",
            "'name' => ['string', 'nullable']",
            "'status_id' => ['int', 'nullable']",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "use Illuminate\Database\Eloquent\Relations\BelongsTo;",
            "use SoftDeletes;",
            "protected \$fillable = [",
            "public function status(): BelongsTo",
            "return \$this->belongsTo(Status::class, 'status_id')",
        ];

        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_posts', $this->migrationPath, $this->filesystem);
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('posts', function (Blueprint \$table) {",
            "table->id();",
            "table->string('name');",
            "table->foreignIdFor(\App\Models\Status::class)",
            "table->timestamps();",
            "table->softDeletes();",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    public function tearDown(): void
    {
        $this->filesystem->delete($this->resourcePath . 'PostResource.php');
        $this->filesystem->delete($this->modelPath . 'Post.php');

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
