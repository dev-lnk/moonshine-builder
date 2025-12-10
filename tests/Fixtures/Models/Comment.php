<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'comment',
        'product_id',
        'moonshine_user_id',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function moonshineUser(): BelongsTo
    {
        return $this->belongsTo(\MoonShine\Laravel\Models\MoonshineUser::class, 'moonshine_user_id');
    }
}
