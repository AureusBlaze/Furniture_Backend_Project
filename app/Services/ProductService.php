<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Str;

class ProductService
{
    public function list(array $filters = []): LengthAwarePaginator
    {
        $query = Product::with(['vendor', 'category', 'tags', 'media', 'arModel'])
            ->active();

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['category_id'])) {
            $query->filterByCategory($filters['category_id']);
        }

        if (! empty($filters['tags'])) {
            // tags can be array of IDs or slugs
            $tagInput = (array) $filters['tags'];
            if (is_numeric($tagInput[0])) {
                $query->filterByTags($tagInput);
            } else {
                $query->filterByTagSlugs($tagInput);
            }
        }

        if (isset($filters['min_price']) || isset($filters['max_price'])) {
            $query->priceRange($filters['min_price'] ?? null, $filters['max_price'] ?? null);
        }

        if (! empty($filters['vendor_id'])) {
            $query->forVendor($filters['vendor_id']);
        }

        $sortField     = $filters['sort_by'] ?? 'created_at';
        $sortDirection = $filters['sort_direction'] ?? 'desc';
        $allowedSorts  = ['price', 'name', 'created_at', 'stock'];

        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDirection);
        }

        return $query->paginate($filters['per_page'] ?? 15);
    }

    public function store(array $data): Product
    {
        $data['slug'] = Str::slug($data['name']) . '-' . uniqid();

        $product = Product::create($data);

        if (! empty($data['tags'])) {
            $product->tags()->sync($data['tags']);
        }

        return $product->load(['vendor', 'category', 'tags', 'media', 'arModel']);
    }

    public function update(Product $product, array $data): Product
    {
        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . uniqid();
        }

        $product->update($data);

        if (isset($data['tags'])) {
            $product->tags()->sync($data['tags']);
        }

        return $product->load(['vendor', 'category', 'tags', 'media', 'arModel']);
    }

    public function delete(Product $product): void
    {
        $product->delete();
    }
}
