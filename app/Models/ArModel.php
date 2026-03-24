<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ArModel extends Model
{
    use HasFactory;

    protected $table = 'ar_models';

    protected $fillable = [
        'product_id',
        'file_url',
        'format',
        'file_size',
        'thumbnail_url',
    ];

    protected $casts = [
        'file_size' => 'integer',
    ];

    // ——— Relationships ———

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
