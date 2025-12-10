<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property string $title
 * @property string|null $content
 * @property int $price
 * @property int $sort_number
 * @property int $category_id
 * @property int $moonshine_user_id
 * @property bool $is_active
 */
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

    protected $casts = [
        'price' => 'integer',
        'sort_number' => 'integer',
        'is_active' => 'boolean',
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
        return $this->belongsTo(\MoonShine\Laravel\Models\MoonshineUser::class, 'moonshine_user_id');
    }
}
