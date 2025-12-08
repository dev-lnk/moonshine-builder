<?php

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class BelongsToManyBuildTest extends TestCase
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
        $this->artisan('moonshine:build belongs_to_many.json');

        $this->item($this->resourcePath . 'Item/ItemResource.php', $this->modelPath . 'Item.php');
        $this->property($this->resourcePath . 'Property/PropertyResource.php', $this->modelPath . 'Property.php');

        $migrationFile = $this->getMigrationFile('create_item_property');
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('item_property', function (Blueprint \$table) {",
            "table->id();",
            "table->foreignIdFor(\App\Models\Item::class, 'item_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\App\Models\Property::class, 'property_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function item(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use App\MoonShine\Resources\Item\Pages\ItemIndexPage;",
            "use App\MoonShine\Resources\Item\Pages\ItemFormPage;",
            "use App\MoonShine\Resources\Item\Pages\ItemDetailPage;",
            "@extends ModelResource<Item, ItemIndexPage, ItemFormPage, ItemDetailPage>",
            "protected array \$with = ['properties'];",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "use Illuminate\Database\Eloquent\Relations\BelongsToMany;",
            "public function properties(): BelongsToMany",
            "return \$this->belongsToMany(Property::class);",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_items');
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('items', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
            "table->timestamps();",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function property(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use App\MoonShine\Resources\Property\Pages\PropertyIndexPage;",
            "use App\MoonShine\Resources\Property\Pages\PropertyFormPage;",
            "use App\MoonShine\Resources\Property\Pages\PropertyDetailPage;",
            "protected array \$with = ['items'];",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "use Illuminate\Database\Eloquent\Relations\BelongsToMany;",
            "public function items(): BelongsToMany",
            "return \$this->belongsToMany(Item::class);",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_properties');
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('properties', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    private function getMigrationFile(string $migrationName): string
    {
        $migrationFile = '';
        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migration) {
            if(str_contains($migration, $migrationName)) {
                $migrationFile = $migration;

                break;
            }
        }

        return $migrationFile;
    }

    public function tearDown(): void
    {
        $this->filesystem->delete($this->resourcePath);

        $this->filesystem->delete($this->modelPath . 'Item.php');
        $this->filesystem->delete($this->modelPath . 'Property.php');

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
