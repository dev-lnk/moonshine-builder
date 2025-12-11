<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class ModelBuildTest extends TestCase
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
        
        $this->loadFixtureModels();
    }
    
    private function loadFixtureModels(): void
    {
        $fixtureModels = realpath('./tests/Fixtures/Models');
        
        foreach ($this->filesystem->files($fixtureModels) as $file) {
            require_once $file->getPathname();
        }
    }

    /**
     * @throws FileNotFoundException
     */
    #[Test]
    public function buildFromProductModel(): void
    {
        $this->artisan('moonshine:build-model', ['entity' => 'App\\Models\\Product']);

        $this->product(
            $this->resourcePath . 'Product/ProductResource.php',
            $this->modelPath . 'Product.php'
        );
    }

    /**
     * @throws FileNotFoundException
     */
    #[Test]
    public function buildFromTestModelWithDifferentTypeSources(): void
    {
        $this->artisan('moonshine:build-model', ['entity' => 'App\\Models\\TestModel']);

        $indexPage = $this->resourcePath . 'TestModel/Pages/TestModelIndexPage.php';
        
        $this->testBuildFile($indexPage, [
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\UI\Fields\Number;",
            "use MoonShine\UI\Fields\Switcher;",
            "Text::make('FromDocblock', 'from_docblock')",
            "Text::make('NullableFromDocblock', 'nullable_from_docblock')",
            "Number::make('FloatFromDocblock', 'float_from_docblock')",
            "Switcher::make('BoolFromDocblock', 'bool_from_docblock')",
            "Number::make('FromCast', 'from_cast')",
            "Text::make('FromFillableOnly', 'from_fillable_only')",
        ]);
    }

    #[Test]
    public function buildAllModels(): void
    {
        $fixtureModels = realpath('./tests/Fixtures/Models');
        
        foreach ($this->filesystem->files($fixtureModels) as $file) {
            $this->filesystem->copy(
                $file->getPathname(),
                $this->modelPath . $file->getFilename()
            );
        }

        $this->artisan('moonshine:build-model', ['--all' => true]);

        $this->assertFileExists($this->resourcePath . 'Product/ProductResource.php');
        $this->assertFileExists($this->resourcePath . 'Category/CategoryResource.php');
        $this->assertFileExists($this->resourcePath . 'Comment/CommentResource.php');
        $this->assertFileExists($this->resourcePath . 'TestModel/TestModelResource.php');
    }

    /**
     * @throws FileNotFoundException
     */
    private function product(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\Models\Product;",
            "@extends ModelResource<Product, ProductIndexPage, ProductFormPage, ProductDetailPage>",
            "use App\MoonShine\Resources\Product\Pages\ProductIndexPage;",
            "use App\MoonShine\Resources\Product\Pages\ProductFormPage;",
            "use App\MoonShine\Resources\Product\Pages\ProductDetailPage;",
            "protected array \$with = ['category', 'comments', 'moonshineUser'];",
            "protected string \$model = Product::class;",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\UI\Fields\Number;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsTo;",
            "use MoonShine\Laravel\Fields\Relationships\HasMany;",
            "use MoonShine\UI\Fields\Switcher;",
            "ID::make('id')",
            "Text::make('Title', 'title')",
            "Text::make('Content', 'content')",
            "Number::make('Price', 'price')",
            "Number::make('SortNumber', 'sort_number')",
            "BelongsTo::make('Category', 'category'",
            "HasMany::make('Comments', 'comments'",
            "BelongsTo::make('MoonshineUser', 'moonshineUser'",
            "Switcher::make('IsActive', 'is_active')",
        ];

        $indexPage = $this->resourcePath . 'Product/Pages/ProductIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Product\ProductResource;",
            "@extends IndexPage<ProductResource>",
        ]);

        $formPage = $this->resourcePath . 'Product/Pages/ProductFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Product\ProductResource;",
            "@extends FormPage<ProductResource>",
            "'title' => ['string', 'required']",
            "'content' => ['string', 'nullable']",
            "'price' => ['int', 'required']",
            "'sort_number' => ['int', 'required']",
            "'category_id' => ['int', 'required']",
            "'moonshine_user_id' => ['int', 'required']",
            "'is_active' => ['boolean', 'required']",
        ]);

        $detailPage = $this->resourcePath . 'Product/Pages/ProductDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Product\ProductResource;",
            "@extends DetailPage<ProductResource>",
        ]);
    }

    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->resourcePath);
        
        if ($this->filesystem->exists($this->modelPath)) {
            foreach ($this->filesystem->files($this->modelPath) as $file) {
                $this->filesystem->delete($file->getPathname());
            }
        }

        parent::tearDown();
    }
}
