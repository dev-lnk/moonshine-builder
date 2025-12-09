<?php

declare(strict_types=1);

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $from_docblock
 * @property string|null $nullable_from_docblock
 * @property float $float_from_docblock
 * @property bool $bool_from_docblock
 */
class TestModel extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'from_docblock',
        'nullable_from_docblock',
        'float_from_docblock',
        'bool_from_docblock',
        'from_cast',
        'from_fillable_only',
    ];

    protected $casts = [
        'from_cast' => 'integer',
    ];
}
