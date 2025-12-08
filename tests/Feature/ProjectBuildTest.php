<?php

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use App\MoonShine\Resources\Product\ProductResource;
use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use MoonShine\Laravel\Pages\Crud\IndexPage;
use PHPUnit\Framework\Attributes\Test;

class ProjectBuildTest extends TestCase
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
        $this->artisan('moonshine:build project.json --type=json');

        $this->category($this->resourcePath . 'Category/CategoryResource.php', $this->modelPath . 'Category.php');
        $this->product($this->resourcePath . 'Product/ProductResource.php', $this->modelPath . 'Product.php');
        $this->comments($this->resourcePath . 'Comment/CommentResource.php', $this->modelPath . 'Comment.php');
    }

    /**
     * @throws FileNotFoundException
     */
    private function category(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "use App\Models\Category;",
            "use App\MoonShine\Resources\Category\Pages\CategoryIndexPage;",
            "use App\MoonShine\Resources\Category\Pages\CategoryFormPage;",
            "use App\MoonShine\Resources\Category\Pages\CategoryDetailPage;",
            "@extends ModelResource<Category, CategoryIndexPage, CategoryFormPage, CategoryDetailPage>",
            "protected string \$model = Category::class;",
            "protected string \$column = 'name';",
            "protected string \$title = 'Category';",
        ]);

        $this->testBuildFile($modelPath, [
            "class Category extends Model",
            "public \$timestamps = false;",
            "protected \$fillable = [\n\t\t'name'",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_categories');
        $this->testBuildFile($migrationFile, [
            "Schema::create('categories', function (Blueprint \$table) {",
            "\$table->id();",
            "\$table->string('name', 100);",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\ID;",
            "ID::make('id')",
            "Text::make('Name', 'name')",
        ];
        $indexPage = $this->resourcePath . 'Category/Pages/CategoryIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Category\CategoryResource;",
            "@extends IndexPage<CategoryResource>",
        ]);

        $formPage = $this->resourcePath . 'Category/Pages/CategoryFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Category\CategoryResource;",
            "@extends FormPage<CategoryResource>",
            "'name' => ['string', 'required']",
        ]);

        $detailPage = $this->resourcePath . 'Category/Pages/CategoryDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Category\CategoryResource;",
            "@extends DetailPage<CategoryResource>",
        ]);
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
            "protected string \$model = Product::class;"
        ]);

        $this->testBuildFile($modelPath, [
            "use Illuminate\Database\Eloquent\SoftDeletes;",
            "use Illuminate\Database\Eloquent\Relations\BelongsTo;",
            "use Illuminate\Database\Eloquent\Relations\HasMany;",
            "class Product extends Model",
            "use SoftDeletes;",
            "protected \$fillable = [\n\t\t'title',\n\t\t'content',\n\t\t'price',\n\t\t'sort_number',\n\t\t'category_id',\n\t\t'moonshine_user_id',\n\t\t'is_active',",
            "public function category(): BelongsTo",
            "return \$this->belongsTo(Category::class, 'category_id');",
            "public function comments(): HasMany",
            "return \$this->hasMany(Comment::class, 'product_id');",
            "public function moonshineUser(): BelongsTo",
            "return \$this->belongsTo(\\MoonShine\\Laravel\\Models\\MoonshineUser::class, 'moonshine_user_id');",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_products');
        $this->testBuildFile($migrationFile, [
            "Schema::create('products', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
            "table->text('content')->nullable();",
            "table->unsignedInteger('price')->default(0)->index()",
            "table->foreignIdFor(\App\Models\Category::class, 'category_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\\MoonShine\\Laravel\\Models\\MoonshineUser::class, 'moonshine_user_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->boolean('is_active')->default(0);",
            "table->timestamps();",
            "table->softDeletes();",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\UI\Fields\Number;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsTo;",
            "use MoonShine\Laravel\Fields\Relationships\HasMany;",
            "use MoonShine\UI\Fields\Checkbox;",
            "ID::make('id')\n\t\t\t\t->sortable()",
            "Text::make('Name', 'title')",
            "Text::make('Content', 'content')",
            "Number::make('Price', 'price')\n\t\t\t\t->default(0)\n\t\t\t\t->sortable()",
            "Number::make('Sorting', 'sort_number')",
            "BelongsTo::make('Category', 'category', resource: CategoryResource::class)",
            "HasMany::make('Comments', 'comments', resource: CommentResource::class)->creatable()",
            "BelongsTo::make('User', 'moonshineUser', resource: MoonShineUserResource::class)",
            "Checkbox::make('Active', 'is_active')",
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

    /**
     * @throws FileNotFoundException
     */
    private function comments(string $resourcePath, string $modelPath): void
    {
        $this->testBuildFile($resourcePath, [
            "class CommentResource extends ModelResource",
        ]);

        $this->testBuildFile($modelPath, [
            "use Illuminate\Database\Eloquent\Relations\BelongsTo;",
            "class Comment extends Model",
            "public \$timestamps = false;",
            "protected \$fillable = [\n\t\t'comment',\n\t\t'product_id',\n\t\t'moonshine_user_id',",
            "public function product(): BelongsTo",
            "return \$this->belongsTo(Product::class, 'product_id');",
            "public function moonshineUser(): BelongsTo",
            "return \$this->belongsTo(\\MoonShine\\Laravel\\Models\\MoonshineUser::class, 'moonshine_user_id');",
        ]);

        $migrationFile = $this->getMigrationFile($this->migrationPath, 'create_comments');
        $this->testBuildFile($migrationFile, [
            "Schema::create('comments', function (Blueprint \$table) {",
            "table->string('comment');",
            "table->foreignIdFor(\App\Models\Product::class, 'product_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\\MoonShine\\Laravel\\Models\\MoonshineUser::class, 'moonshine_user_id')\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
        ]);

        $resourceFields = [
            "use MoonShine\UI\Fields\ID;",
            "use MoonShine\UI\Fields\Text;",
            "use MoonShine\Laravel\Fields\Relationships\BelongsTo;",
            "ID::make('id')",
            "Text::make('Comment', 'comment')",
            "BelongsTo::make('Product', 'product', resource: ProductResource::class)",
            "BelongsTo::make('User', 'moonshineUser', resource: MoonShineUserResource::class)",
        ];

        $indexPage = $this->resourcePath . 'Comment/Pages/CommentIndexPage.php';
        $this->testBuildFile($indexPage, $resourceFields + [
            "use App\MoonShine\Resources\Comment\CommentResource;",
            "@extends IndexPage<CommentResource>",
        ]);

        $formPage = $this->resourcePath . 'Comment/Pages/CommentFormPage.php';
        $this->testBuildFile($formPage, $resourceFields + [
            "use App\MoonShine\Resources\Comment\CommentResource;",
            "@extends FormPage<CommentResource>",
            "'comment' => ['string', 'required'],",
            "'product_id' => ['int', 'required'],",
            "'moonshine_user_id' => ['int', 'required'],",
        ]);

        $detailPage = $this->resourcePath . 'Comment/Pages/CommentDetailPage.php';
        $this->testBuildFile($detailPage, $resourceFields + [
            "use App\MoonShine\Resources\Comment\CommentResource;",
            "@extends DetailPage<CommentResource>",
        ]);
    }

    public function tearDown(): void
    {
        $this->filesystem->deleteDirectory($this->resourcePath);

        $this->filesystem->delete($this->modelPath . 'Category.php');
        $this->filesystem->delete($this->modelPath . 'Product.php');
        $this->filesystem->delete($this->modelPath . 'Comment.php');

        $migrations = $this->filesystem->allFiles($this->migrationPath);
        foreach ($migrations as $migrationFile) {
            $this->filesystem->delete($migrationFile);
        }

        parent::tearDown();
    }
}
