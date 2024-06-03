## Resource example
```php
<?php

declare(strict_types=1);

namespace App\MoonShine\Resources;

use Illuminate\Database\Eloquent\Model;
use App\Models\Product;

use MoonShine\Resources\ModelResource;
use MoonShine\Decorations\Block;
use MoonShine\Fields\ID;
use MoonShine\Fields\Text;
use MoonShine\Fields\Number;
use MoonShine\Fields\Relationships\BelongsTo;
use MoonShine\Fields\Relationships\HasMany;
use MoonShine\Fields\Checkbox;

/**
 * @extends ModelResource<Product>
 */
class ProductResource extends ModelResource
{
    protected string $model = Product::class;

    protected string $title = 'ProductResource';

    public function fields(): array
    {
        return [
            Block::make([
                ID::make('id')
                    ->sortable(),
                Text::make('Name', 'title'),
                Text::make('Content', 'content'),
                Number::make('Price', 'price')
                    ->default(0)
                    ->sortable(),
                Number::make('Sorting', 'sort_number')
                    ->default(0)
                    ->sortable(),
                BelongsTo::make('Category', 'category', resource: new CategoryResource()),
                HasMany::make('Comments', 'comments', resource: new CommentResource())
                    ->creatable(),
                BelongsTo::make('User', 'moonshineUser', resource: new \MoonShine\Resources\MoonShineUserResource()),
                Checkbox::make('Active', 'is_active'),
            ]),
        ];
    }

    public function rules(Model $item): array
    {
        return [
            'id' => ['int', 'nullable'],
            'title' => ['string', 'nullable'],
            'content' => ['string', 'nullable'],
            'price' => ['int', 'nullable'],
            'sort_number' => ['int', 'nullable'],
            'category_id' => ['int', 'nullable'],
            'moonshine_user_id' => ['int', 'nullable'],
            'is_active' => ['accepted', 'sometimes'],
        ];
    }
}
```