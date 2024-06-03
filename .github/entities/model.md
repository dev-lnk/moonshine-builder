## Model example
```php
<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'title',
        'content',
        'price',
        'sort_number',
        'category_id',
        'moonshine_user_id',
        'is_active',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'category_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class, 'product_id');
    }

    public function moonshineUser(): BelongsTo
    {
        return $this->belongsTo(\MoonShine\Models\MoonshineUser::class, 'moonshine_user_id');
    }
}
```