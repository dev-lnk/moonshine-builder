<?php

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
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
    }

    public function tearDown(): void
    {
        $this->filesystem->delete($this->resourcePath);

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
