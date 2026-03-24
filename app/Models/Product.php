<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Product extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'vendor_id',
        'category_id',
        'name',
        'slug',
        'description',
        'price',
        'stock',
        'is_active',
        'weight',
        'dimensions',
        'material',
    ];

    protected $casts = [
        'price'     => 'decimal:2',
        'stock'     => 'integer',
        'is_active' => 'boolean',
        'weight'    => 'decimal:2',
    ];

    // ——— Relationships ———

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'product_tag');
    }

    public function media(): BelongsToMany
    {
        return $this->belongsToMany(Media::class, 'product_media')
            ->withPivot(['is_primary', 'sort_order'])
            ->orderByPivot('sort_order');
    }

    public function primaryMedia(): BelongsToMany
    {
        return $this->media()->wherePivot('is_primary', true);
    }

    public function orderItems(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function arModel(): HasOne
    {
        return $this->hasOne(ArModel::class);
    }

    // ——— Scopes ———

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSearch($query, string $term)
    {
        return $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('description', 'like', "%{$term}%")
              ->orWhere('material', 'like', "%{$term}%");
        });
    }

    public function scopeFilterByCategory($query, int|array $categoryId)
    {
        return $query->whereIn('category_id', (array) $categoryId);
    }

    public function scopeFilterByTags($query, array $tagIds)
    {
        return $query->whereHas('tags', fn($q) => $q->whereIn('tags.id', $tagIds));
    }

    public function scopeFilterByTagSlugs($query, array $tagSlugs)
    {
        return $query->whereHas('tags', fn($q) => $q->whereIn('tags.slug', $tagSlugs));
    }

    public function scopePriceRange($query, ?float $min, ?float $max)
    {
        if ($min !== null) {
            $query->where('price', '>=', $min);
        }
        if ($max !== null) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    public function scopeForVendor($query, int $vendorId)
    {
        return $query->where('vendor_id', $vendorId);
    }
}
