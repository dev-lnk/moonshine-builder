<?php

declare(strict_types=1);

namespace DevLnk\MoonShineBuilder\Tests\Feature;

use DevLnk\MoonShineBuilder\Enums\SqlTypeMap;
use DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructureList;
use DevLnk\MoonShineBuilder\Services\CodeStructure\Factories\StructureFromModel;
use DevLnk\MoonShineBuilder\Tests\Fixtures\Models\Category;
use DevLnk\MoonShineBuilder\Tests\Fixtures\Models\Point;
use DevLnk\MoonShineBuilder\Tests\Fixtures\Models\Role;
use DevLnk\MoonShineBuilder\Tests\Fixtures\Models\User;
use DevLnk\MoonShineBuilder\Tests\TestCase;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\Attributes\Test;

class StructureFromModelTest extends TestCase
{
    protected Filesystem $filesystem;

    public function setUp(): void
    {
        parent::setUp();
        $this->filesystem = new Filesystem();
        $this->disableConfirmationPrompts();
    }

    private function disableConfirmationPrompts(): void
    {
        config()->set('moonshine_builder.is_confirm_replace_files', false);
        config()->set('moonshine_builder.is_confirm_change_provider', false);
        config()->set('moonshine_builder.is_confirm_change_menu', false);
    }

    private function getUserStructure(): \DevLnk\MoonShineBuilder\Services\CodeStructure\CodeStructure
    {
        return StructureFromModel::fromModel(User::class)->makeStructure(User::class);
    }

    private function createTestJsonFile(string $filename, array $data): string
    {
        $json = json_encode($data, JSON_PRETTY_PRINT);
        $filePath = base_path("builds/{$filename}");
        file_put_contents($filePath, $json);
        return $filePath;
    }

    private function cleanupAfterIntegrationTest(string $jsonFile, string $resourceName): void
    {
        if (file_exists($jsonFile)) {
            unlink($jsonFile);
        }

        $resourceDir = config('moonshine.dir') . "/Resources/{$resourceName}";
        if ($this->filesystem->isDirectory($resourceDir)) {
            $this->filesystem->deleteDirectory($resourceDir);
        }

        $modelFile = app_path("Models/{$resourceName}.php");
        if ($this->filesystem->exists($modelFile)) {
            $this->filesystem->delete($modelFile);
        }
    }

    private function buildResourceJsonData(string $name, array $fields, bool $timestamps = true): array
    {
        return [
            'resources' => [
                [
                    'name' => $name,
                    'timestamps' => $timestamps,
                    'soft_deletes' => false,
                    'withModel' => true,
                    'withMigration' => true,
                    'fields' => $fields,
                ],
            ],
        ];
    }

    #[Test]
    public function it_generates_structure_for_four_models(): void
    {
        $models = [
            User::class,
            Category::class,
            Role::class,
            Point::class,
        ];

        $codeStructures = new CodeStructureList();

        foreach ($models as $modelClass) {
            $codeStructures->addCodeStructure(
                StructureFromModel::fromModel($modelClass)->makeStructure($modelClass)
            );
        }

        $structures = $codeStructures->codeStructures();

        $this->assertCount(4, $structures);

        $structureNames = array_map(
            fn ($s) => $s->entity()->ucFirst(),
            $structures
        );

        $this->assertContains('User', $structureNames);
        $this->assertContains('Category', $structureNames);
        $this->assertContains('Role', $structureNames);
        $this->assertContains('Point', $structureNames);
    }

    #[Test]
    public function it_generates_user_structure_with_three_relations(): void
    {
        $structure = $this->getUserStructure();

        $this->assertEquals('User', $structure->entity()->ucFirst());
        $this->assertEquals('users', $structure->table());

        // User should have BelongsTo (role), HasMany (points), BelongsToMany (categories)
        $this->assertTrue($structure->hasBelongsTo(), 'User should have BelongsTo relation');
        $this->assertTrue($structure->hasHasMany(), 'User should have HasMany relation');
        $this->assertTrue($structure->hasBelongsToMany(), 'User should have BelongsToMany relation');

        $columns = $structure->columns();
        $columnNames = array_map(fn ($c) => $c->column(), $columns);

        // Check basic columns exist
        $this->assertContains('id', $columnNames);
        $this->assertContains('name', $columnNames);
        $this->assertContains('email', $columnNames);

        // Check relation columns
        $relationColumns = array_filter($columns, fn ($c) => $c->relation() !== null);
        $this->assertCount(3, $relationColumns, 'User should have 3 relation columns');

        // Check BelongsTo relation (role_id -> roles)
        $roleColumn = $this->findColumnByName($columns, 'role_id');
        $this->assertNotNull($roleColumn, 'role_id column should exist');
        $this->assertEquals(SqlTypeMap::BELONGS_TO, $roleColumn->type());
        $this->assertNotNull($roleColumn->relation());
        $this->assertEquals('roles', $roleColumn->relation()->table()->raw());

        // Check HasMany relation (points)
        $pointsColumn = $this->findColumnByName($columns, 'points');
        $this->assertNotNull($pointsColumn, 'points column should exist for HasMany');
        $this->assertEquals(SqlTypeMap::HAS_MANY, $pointsColumn->type());
        $this->assertNotNull($pointsColumn->relation());
        $this->assertEquals('points', $pointsColumn->relation()->table()->raw());

        // Check BelongsToMany relation (categories)
        $categoriesColumn = $this->findColumnByName($columns, 'categories');
        $this->assertNotNull($categoriesColumn, 'categories column should exist for BelongsToMany');
        $this->assertEquals(SqlTypeMap::BELONGS_TO_MANY, $categoriesColumn->type());
        $this->assertNotNull($categoriesColumn->relation());
        $this->assertEquals('categories', $categoriesColumn->relation()->table()->raw());
    }

    #[Test]
    public function it_generates_correct_json_for_resources(): void
    {
        $models = [
            User::class,
            Category::class,
            Role::class,
            Point::class,
        ];

        $codeStructures = new CodeStructureList();

        foreach ($models as $modelClass) {
            $codeStructures->addCodeStructure(
                StructureFromModel::fromModel($modelClass)->makeStructure($modelClass)
            );
        }

        $json = $codeStructures->toJson();
        $data = json_decode($json, true);

        $this->assertArrayHasKey('resources', $data);
        $this->assertCount(4, $data['resources']);

        $resourceNames = array_column($data['resources'], 'name');
        $this->assertContains('User', $resourceNames);
        $this->assertContains('Category', $resourceNames);
        $this->assertContains('Role', $resourceNames);
        $this->assertContains('Point', $resourceNames);

        // Find User resource and check relations
        $userResource = $this->findResourceByName($data['resources'], 'User');
        $this->assertNotNull($userResource);

        $relationFields = array_filter(
            $userResource['fields'],
            fn ($f) => isset($f['relation'])
        );
        $this->assertCount(3, $relationFields, 'User resource should have 3 relation fields');

        // Check BelongsTo relation field
        $roleField = $this->findFieldByColumn($userResource['fields'], 'role_id');
        $this->assertNotNull($roleField);
        $this->assertEquals(SqlTypeMap::BELONGS_TO->value, $roleField['type']);
        $this->assertEquals('roles', $roleField['relation']['table']);

        // Check HasMany relation field
        $pointsField = $this->findFieldByColumn($userResource['fields'], 'points');
        $this->assertNotNull($pointsField);
        $this->assertEquals(SqlTypeMap::HAS_MANY->value, $pointsField['type']);
        $this->assertEquals('points', $pointsField['relation']['table']);

        // Check BelongsToMany relation field
        $categoriesField = $this->findFieldByColumn($userResource['fields'], 'categories');
        $this->assertNotNull($categoriesField);
        $this->assertEquals(SqlTypeMap::BELONGS_TO_MANY->value, $categoriesField['type']);
        $this->assertEquals('categories', $categoriesField['relation']['table']);
    }

    #[Test]
    public function it_detects_timestamps(): void
    {
        $structure = $this->getUserStructure();
        $this->assertTrue($structure->isTimestamps());

        $categoryStructure = StructureFromModel::fromModel(Category::class)->makeStructure(Category::class);
        $this->assertTrue($categoryStructure->isTimestamps());
    }

    #[Test]
    public function it_processes_casts_correctly(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        $isActiveColumn = $this->findColumnByName($columns, 'is_active');
        $this->assertNotNull($isActiveColumn);
        $this->assertEquals('bool', $isActiveColumn->getCast());
    }

    #[Test]
    public function it_handles_different_cast_types(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        // Test boolean cast
        $isActiveColumn = $this->findColumnByName($columns, 'is_active');
        $this->assertNotNull($isActiveColumn);
        $this->assertEquals('bool', $isActiveColumn->getCast());
        $this->assertEquals(SqlTypeMap::BOOLEAN, $isActiveColumn->type());

        // Test string fields
        $nameColumn = $this->findColumnByName($columns, 'name');
        $this->assertNotNull($nameColumn);
        $this->assertEquals(SqlTypeMap::STRING, $nameColumn->type());

        $emailColumn = $this->findColumnByName($columns, 'email');
        $this->assertNotNull($emailColumn);
        $this->assertEquals(SqlTypeMap::STRING, $emailColumn->type());
    }

    #[Test]
    public function it_excludes_columns_not_in_fillable(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();
        $columnNames = array_map(fn ($c) => $c->column(), $columns);

        // Should not include hidden fields if they are not in fillable
        $this->assertNotContains('remember_token', $columnNames);
    }

    #[Test]
    public function it_detects_primary_key(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        $idColumn = $this->findColumnByName($columns, 'id');
        $this->assertNotNull($idColumn);
        $this->assertEquals(SqlTypeMap::ID, $idColumn->type());
        $this->assertFalse($idColumn->isNullable());
    }

    #[Test]
    public function it_generates_correct_table_name(): void
    {
        $userStructure = StructureFromModel::fromModel(User::class)->makeStructure(User::class);
        $this->assertEquals('users', $userStructure->table());

        $categoryStructure = StructureFromModel::fromModel(Category::class)->makeStructure(Category::class);
        $this->assertEquals('categories', $categoryStructure->table());

        $pointStructure = StructureFromModel::fromModel(Point::class)->makeStructure(Point::class);
        $this->assertEquals('points', $pointStructure->table());
    }

    #[Test]
    public function it_handles_belongs_to_foreign_keys(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        $roleIdColumn = $this->findColumnByName($columns, 'role_id');
        $this->assertNotNull($roleIdColumn, 'role_id should exist as BelongsTo relation');
        $this->assertEquals(SqlTypeMap::BELONGS_TO, $roleIdColumn->type());

        $relation = $roleIdColumn->relation();
        $this->assertNotNull($relation);
        $this->assertEquals('roles', $relation->table()->raw());
        $this->assertEquals('role', $relation->modelRelationName());
        $this->assertEquals('id', $relation->foreignColumn());
    }

    #[Test]
    public function it_generates_belongs_to_many_with_correct_structure(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        $categoriesColumn = $this->findColumnByName($columns, 'categories');
        $this->assertNotNull($categoriesColumn);
        $this->assertEquals(SqlTypeMap::BELONGS_TO_MANY, $categoriesColumn->type());
        $this->assertEquals('[]', $categoriesColumn->default());
        $this->assertFalse($categoriesColumn->isNullable());

        $relation = $categoriesColumn->relation();
        $this->assertNotNull($relation);
        $this->assertEquals('categories', $relation->table()->raw());
        $this->assertEquals('categories', $relation->modelRelationName());
    }

    #[Test]
    public function it_generates_has_many_with_correct_structure(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();

        $pointsColumn = $this->findColumnByName($columns, 'points');
        $this->assertNotNull($pointsColumn);
        $this->assertEquals(SqlTypeMap::HAS_MANY, $pointsColumn->type());
        $this->assertEquals('[]', $pointsColumn->default());
        $this->assertFalse($pointsColumn->isNullable());

        $relation = $pointsColumn->relation();
        $this->assertNotNull($relation);
        $this->assertEquals('points', $relation->table()->raw());
        $this->assertEquals('points', $relation->modelRelationName());
    }

    #[Test]
    public function it_includes_timestamps_when_model_uses_timestamps(): void
    {
        $structure = $this->getUserStructure();
        $columns = $structure->columns();
        $columnNames = array_map(fn ($c) => $c->column(), $columns);

        $this->assertTrue($structure->isTimestamps());
        $this->assertContains('created_at', $columnNames);
        $this->assertContains('updated_at', $columnNames);

        $createdAtColumn = $this->findColumnByName($columns, 'created_at');
        $this->assertNotNull($createdAtColumn);
        $this->assertEquals(SqlTypeMap::TIMESTAMP, $createdAtColumn->type());
        $this->assertTrue($createdAtColumn->isNullable());

        $updatedAtColumn = $this->findColumnByName($columns, 'updated_at');
        $this->assertNotNull($updatedAtColumn);
        $this->assertEquals(SqlTypeMap::TIMESTAMP, $updatedAtColumn->type());
        $this->assertTrue($updatedAtColumn->isNullable());
    }

    #[Test]
    public function it_replaces_foreign_key_column_with_relation(): void
    {
        $structure = $this->getUserStructure();

        // role_id should exist only once as a BelongsTo relation, not as a plain column
        $columns = $structure->columns();
        $roleIdColumns = array_filter($columns, fn ($c) => $c->column() === 'role_id');
        $this->assertCount(1, $roleIdColumns, 'role_id should appear only once');

        $roleIdColumn = $this->findColumnByName($columns, 'role_id');
        $this->assertEquals(SqlTypeMap::BELONGS_TO, $roleIdColumn->type(), 'role_id should be a BelongsTo type');
    }

    #[Test]
    public function it_correctly_serializes_to_json(): void
    {
        $structure = $this->getUserStructure();
        $codeStructures = new CodeStructureList();
        $codeStructures->addCodeStructure($structure);

        $json = $codeStructures->toJson();
        $this->assertJson($json);

        $data = json_decode($json, true);
        $this->assertIsArray($data);
        $this->assertArrayHasKey('resources', $data);
        $this->assertCount(1, $data['resources']);

        $userResource = $data['resources'][0];
        $this->assertEquals('User', $userResource['name']);
        $this->assertArrayHasKey('timestamps', $userResource);
        $this->assertTrue($userResource['timestamps']);
        $this->assertArrayHasKey('fields', $userResource);
        $this->assertIsArray($userResource['fields']);
        $this->assertNotEmpty($userResource['fields']);

        // Check that all fields have required structure
        foreach ($userResource['fields'] as $field) {
            $this->assertArrayHasKey('column', $field);
            $this->assertArrayHasKey('type', $field);
            $this->assertArrayHasKey('name', $field);
        }
    }

    #[Test]
    public function it_includes_with_property_for_relations(): void
    {
        $structure = $this->getUserStructure();
        $moonShineStructure = new \DevLnk\MoonShineBuilder\Services\CodeStructure\MoonShineStructure($structure);

        $withProperty = $moonShineStructure->getWithProperty();

        $this->assertIsArray($withProperty);
        $this->assertNotEmpty($withProperty, 'User model should have relations in $with property');

        // User has 3 relations: role (BelongsTo), points (HasMany), categories (BelongsToMany)
        $this->assertCount(3, $withProperty, 'User should have 3 relations');
        $this->assertContains('role', $withProperty, 'User should have role relation');
        $this->assertContains('points', $withProperty, 'User should have points relation');
        $this->assertContains('categories', $withProperty, 'User should have categories relation');
    }

    #[Test]
    public function it_does_not_include_with_property_for_model_without_relations(): void
    {
        $structure = StructureFromModel::fromModel(Role::class)->makeStructure(Role::class);
        $moonShineStructure = new \DevLnk\MoonShineBuilder\Services\CodeStructure\MoonShineStructure($structure);

        $withProperty = $moonShineStructure->getWithProperty();

        $this->assertIsArray($withProperty);
        $this->assertEmpty($withProperty, 'Role model should not have relations in $with property');
    }

    #[Test]
    public function it_generates_resource_with_with_property_when_model_has_relations(): void
    {
        $fields = [
            ['column' => 'id', 'type' => 'id', 'name' => 'ID'],
            ['column' => 'name', 'type' => 'string', 'name' => 'Name'],
            [
                'column' => 'role_id',
                'type' => 'BelongsTo',
                'name' => 'Role',
                'relation' => ['table' => 'roles', 'foreign_column' => 'id'],
            ],
            [
                'column' => 'points',
                'type' => 'HasMany',
                'name' => 'Points',
                'relation' => ['table' => 'points', 'foreign_key' => 'user_id', 'foreign_column' => 'id'],
            ],
            [
                'column' => 'categories',
                'type' => 'BelongsToMany',
                'name' => 'Categories',
                'relation' => ['table' => 'categories', 'foreign_key' => 'user_id', 'foreign_column' => 'id'],
            ],
        ];

        $jsonData = $this->buildResourceJsonData('User', $fields);
        $testJsonFile = $this->createTestJsonFile('test_user_with_relations.json', $jsonData);

        try {
            $this->artisan('moonshine:build test_user_with_relations.json --type=json');

            $resourcePath = config('moonshine.dir') . '/Resources/User/UserResource.php';
            $this->assertFileExists($resourcePath);

            $resourceContent = $this->filesystem->get($resourcePath);

            // Check that $with property exists and contains all relations
            $this->assertStringContainsString("protected array \$with = [", $resourceContent, 'Resource should have $with property');
            $this->assertStringContainsString("'role'", $resourceContent, 'Resource $with should contain role relation');
            $this->assertStringContainsString("'points'", $resourceContent, 'Resource $with should contain points relation');
            $this->assertStringContainsString("'categories'", $resourceContent, 'Resource $with should contain categories relation');
        } finally {
            $this->cleanupAfterIntegrationTest($testJsonFile, 'User');
        }
    }

    #[Test]
    public function it_generates_resource_without_with_property_when_model_has_no_relations(): void
    {
        $fields = [
            ['column' => 'id', 'type' => 'id', 'name' => 'ID'],
            ['column' => 'title', 'type' => 'string', 'name' => 'Title'],
            ['column' => 'is_active', 'type' => 'boolean', 'name' => 'Active'],
        ];

        $jsonData = $this->buildResourceJsonData('Role', $fields);
        $testJsonFile = $this->createTestJsonFile('test_role_no_relations.json', $jsonData);

        try {
            $this->artisan('moonshine:build test_role_no_relations.json --type=json');

            $resourcePath = config('moonshine.dir') . '/Resources/Role/RoleResource.php';
            $this->assertFileExists($resourcePath);

            $resourceContent = $this->filesystem->get($resourcePath);

            // Check that $with property does NOT exist
            $this->assertStringNotContainsString("protected array \$with", $resourceContent, 'Resource should not have $with property when model has no relations');
        } finally {
            $this->cleanupAfterIntegrationTest($testJsonFile, 'Role');
        }
    }

    private function findColumnByName(array $columns, string $name): ?object
    {
        foreach ($columns as $column) {
            if ($column->column() === $name) {
                return $column;
            }
        }
        return null;
    }

    private function findResourceByName(array $resources, string $name): ?array
    {
        foreach ($resources as $resource) {
            if ($resource['name'] === $name) {
                return $resource;
            }
        }
        return null;
    }

    private function findFieldByColumn(array $fields, string $column): ?array
    {
        foreach ($fields as $field) {
            if ($field['column'] === $column) {
                return $field;
            }
        }
        return null;
    }
}
