<?php

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Tests\TestCase;
use DevLnk\MoonShineBuilder\Tests\Traits\GetMigrationFile;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class ProjectBuildTest extends TestCase
{
    use GetMigrationFile;

    private string $resourcePath = '';

    private string $modelPath = '';

    private string $migrationPath = '';

    private Filesystem $filesystem;

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

        $this->category($this->resourcePath . 'CategoryResource.php', $this->modelPath . 'Category.php');
        $this->product($this->resourcePath . 'ProductResource.php', $this->modelPath . 'Product.php');
        $this->comments($this->resourcePath . 'CommentResource.php', $this->modelPath . 'Comment.php');
    }

    /**
     * @throws FileNotFoundException
     */
    private function category(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use MoonShine\Fields\ID;",
            "use MoonShine\Fields\Text;",
            "protected string \$column = 'name';",
            "ID::make('id')",
            "Text::make('Name', 'name')",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "class Category extends Model",
            "public \$timestamps = false;",
            "protected \$fillable = [\n\t\t'name'",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_categories', $this->migrationPath, $this->filesystem);


        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('categories', function (Blueprint \$table) {",
            "\$table->id();",
            "\$table->string('name', 100);",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function product(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use MoonShine\Fields\ID;",
            "use MoonShine\Fields\Text;",
            "use MoonShine\Fields\Number;",
            "use MoonShine\Fields\Relationships\BelongsTo;",
            "use MoonShine\Fields\Relationships\HasMany;",
            "use MoonShine\Fields\Checkbox;",
            "@extends ModelResource<Product>",
            "protected array \$with = ['category', 'comments', 'moonshineUser'];",
            "ID::make('id')\n\t\t\t\t\t->sortable()",
            "Text::make('Name', 'title')",
            "Text::make('Content', 'content')",
            "Number::make('Price', 'price')\n\t\t\t\t\t->default(0)\n\t\t\t\t\t->sortable()",
            "Number::make('Sorting', 'sort_number')",
            "BelongsTo::make('Category', 'category', resource: new CategoryResource())",
            "HasMany::make('Comments', 'comments', resource: new CommentResource())\n\t\t\t\t\t->creatable()",
            "BelongsTo::make('User', 'moonshineUser', resource: new \\MoonShine\\Resources\\MoonShineUserResource())",
            "Checkbox::make('Active', 'is_active')",
            "'id' => ['int', 'nullable']",
            "'title' => ['string', 'nullable']",
            "'content' => ['string', 'nullable']",
            "'price' => ['int', 'nullable']",
            "'sort_number' => ['int', 'nullable']",
            "'category_id' => ['int', 'nullable']",
            "'moonshine_user_id' => ['int', 'nullable']",
            "'is_active' => ['accepted', 'sometimes']",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
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
            "return \$this->belongsTo(\\MoonShine\\Models\\MoonshineUser::class, 'moonshine_user_id');",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_products', $this->migrationPath, $this->filesystem);
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('products', function (Blueprint \$table) {",
            "table->id();",
            "table->string('title');",
            "table->text('content');",
            "table->unsignedInteger('price')->default(0)->index()",
            "table->foreignIdFor(\App\Models\Category::class)\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\MoonShine\Models\MoonshineUser::class)\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->boolean('is_active')->default(0);",
            "table->timestamps();",
            "table->softDeletes();",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    /**
     * @throws FileNotFoundException
     */
    private function comments(string $resourcePath, string $modelPath): void
    {
        $this->assertFileExists($resourcePath);
        $this->assertFileExists($modelPath);

        $resource = $this->filesystem->get($resourcePath);
        $resourceStringContains = [
            "use MoonShine\Fields\ID;",
            "use MoonShine\Fields\Text;",
            "use MoonShine\Fields\Relationships\BelongsTo;",
            "@extends ModelResource<Comment>",
            "class CommentResource extends ModelResource",
            "ID::make('id')",
            "Text::make('Comment', 'comment')",
            "BelongsTo::make('Product', 'product', resource: new ProductResource())",
            "BelongsTo::make('User', 'moonshineUser', resource: new \MoonShine\Resources\MoonShineUserResource())",
        ];
        foreach ($resourceStringContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $resource);
        }

        $model = $this->filesystem->get($modelPath);
        $modelContains = [
            "use Illuminate\Database\Eloquent\Relations\BelongsTo;",
            "class Comment extends Model",
            "public \$timestamps = false;",
            "protected \$fillable = [\n\t\t'comment',\n\t\t'product_id',\n\t\t'moonshine_user_id',",
            "public function product(): BelongsTo",
            "return \$this->belongsTo(Product::class, 'product_id');",
            "public function moonshineUser(): BelongsTo",
            "return \$this->belongsTo(\MoonShine\Models\MoonshineUser::class, 'moonshine_user_id');",
        ];
        foreach ($modelContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $model);
        }

        $migrationFile = $this->getMigrationFile('create_comments', $this->migrationPath, $this->filesystem);
        $this->assertNotEmpty($migrationFile);
        $migration = $this->filesystem->get($migrationFile);
        $migrationContains = [
            "Schema::create('comments', function (Blueprint \$table) {",
            "table->string('comment');",
            "table->foreignIdFor(\App\Models\Product::class)\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
            "table->foreignIdFor(\MoonShine\Models\MoonshineUser::class)\n\t\t\t\t->constrained()\n\t\t\t\t->cascadeOnDelete()\n\t\t\t\t->cascadeOnUpdate()",
        ];
        foreach ($migrationContains as $stringContain) {
            $this->assertStringContainsString($stringContain, $migration);
        }
    }

    public function tearDown(): void
    {
        $this->filesystem->delete($this->resourcePath . 'CategoryResource.php');
        $this->filesystem->delete($this->resourcePath . 'ProductResource.php');
        $this->filesystem->delete($this->resourcePath . 'CommentResource.php');

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
