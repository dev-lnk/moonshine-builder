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

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_item_property');
        $this->testBuildFile($migrationFile, [
            "Schema::create('item_property', function (Blueprint \$table) {",
            "table->id();",
            "table->foreignIdFor(\App\Models\Item::class, 'item_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\App\Models\Property::class, 'property_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function item(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\MoonShine\Resources\Item\Pages\ItemIndexPage;",
            "use App\MoonShine\Resources\Item\Pages\ItemFormPage;",
            "use App\MoonShine\Resources\Item\Pages\ItemDetailPage;",
            "@extends ModelResource<Item, ItemIndexPage, ItemFormPage, ItemDetailPage>",
            "protected array \$with = ['properties'];",
        ]);

        $this->testBuildFile($modelPath, [
            "use Illuminate\Database\Eloquent\Relations\BelongsToMany;",
            "public function properties(): BelongsToMany",
            "return \$this->belongsToMany(Property::class);",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_items');
        $this->testBuildFile($migrationFile, [
            "Schema::create('items', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
            "table->timestamps();",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsToMany;",
            "ID::make('id')\n\t\t\t\t->sortable()",
            "Text::make('Name', 'title')",
            "BelongsToMany::make('Properties', 'properties', resource: PropertyResource::class)",
        ];

        $indexPage = $this->resourcePath . 'Item/Pages/ItemIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Item\ItemResource;",
            "@extends IndexPage<ItemResource>",
        ]);

        $formPage = $this->resourcePath . 'Item/Pages/ItemFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Item\ItemResource;",
            "@extends FormPage<ItemResource>",
            "'title' => ['string', 'required']",
            "'properties' => ['array', 'nullable']",
        ]);

        $detailPage = $this->resourcePath . 'Item/Pages/ItemDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Item\ItemResource;",
            "@extends DetailPage<ItemResource>",
        ]);
    }

    /**
     * @throws FileNotFoundException
     */
    private function property(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\MoonShine\Resources\Property\Pages\PropertyIndexPage;",
            "use App\MoonShine\Resources\Property\Pages\PropertyFormPage;",
            "use App\MoonShine\Resources\Property\Pages\PropertyDetailPage;",
            "protected array \$with = ['items'];",
        ]);

        $this->testBuildFile($modelPath, [
            "use Illuminate\Database\Eloquent\Relations\BelongsToMany;",
            "public function items(): BelongsToMany",
            "return \$this->belongsToMany(Item::class);",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_properties');
        $this->testBuildFile($migrationFile, [
            "Schema::create('properties', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsToMany;",
            "ID::make('id')",
            "Text::make('Name', 'title')",
            "BelongsToMany::make('Items', 'items', resource: ItemResource::class)",
        ];

        $indexPage = $this->resourcePath . 'Property/Pages/PropertyIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Property\PropertyResource;",
            "@extends IndexPage<PropertyResource>",
        ]);

        $formPage = $this->resourcePath . 'Property/Pages/PropertyFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Property\PropertyResource;",
            "@extends FormPage<PropertyResource>",
            "'title' => ['string', 'required']",
            "'items' => ['array', 'nullable']",
        ]);

        $detailPage = $this->resourcePath . 'Property/Pages/PropertyDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Property\PropertyResource;",
            "@extends DetailPage<PropertyResource>",
        ]);
    }

    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->resourcePath);

        $this->filesystem->delete($this->modelPath . 'Item.php');
        $this->filesystem->delete($this->modelPath . 'Property.php');

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
