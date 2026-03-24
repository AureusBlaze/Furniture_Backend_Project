<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use OpenAI\Laravel\Facades\OpenAI;

class AiService
{
    /**
     * Analyse a room image and return an array of furniture tag strings.
     *
     * @return string[]
     */
    public function analyzeRoomImage(UploadedFile $image): array
    {
        // Encode image as base64
        $imageData   = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType    = $image->getMimeType();

        $response = OpenAI::chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'You are a furniture expert. Analyse this room image and identify all furniture items and their style attributes (e.g. "Modern", "Sofa", "Wooden", "Minimalist", "Dining Table"). Return ONLY a valid JSON array of tag strings, no explanation. Example: ["Modern","Sofa","Wooden","Coffee Table","Minimalist"]',
                        ],
                        [
                            'type'      => 'image_url',
                            'image_url' => [
                                'url' => "data:{$mimeType};base64,{$imageData}",
                            ],
                        ],
                    ],
                ],
            ],
            'max_tokens' => 300,
        ]);

        $content = $response->choices[0]->message->content ?? '[]';

        // Strip markdown code fences if present
        $content = trim(preg_replace('/^```json\s*|```\s*$/m', '', $content));

        $tags = json_decode($content, true);

        return is_array($tags) ? $tags : [];
    }

    /**
     * Given an array of tag strings, return matching active products.
     */
    public function getSuggestedProducts(array $tags): Collection
    {
        if (empty($tags)) {
            return collect();
        }

        return Product::with(['vendor', 'category', 'tags', 'media', 'arModel'])
            ->active()
            ->where(function ($query) use ($tags) {
                // Match by tag names OR product name/material
                $query->whereHas('tags', fn($q) => $q->whereIn('name', $tags))
                      ->orWhere(function ($q) use ($tags) {
                          foreach ($tags as $tag) {
                              $q->orWhere('name', 'like', "%{$tag}%")
                                ->orWhere('material', 'like', "%{$tag}%")
                                ->orWhere('description', 'like', "%{$tag}%");
                          }
                      });
            })
            ->limit(20)
            ->get();
    }

    /**
     * Auto-tag a product using its images via OpenAI Vision.
     *
     * @return string[]
     */
    public function autoTagProduct(Product $product): array
    {
        $product->load('media');

        // Use up to 3 product images
        $imageContents = [];
        foreach ($product->media->take(3) as $media) {
            $imageContents[] = [
                'type'      => 'image_url',
                'image_url' => ['url' => $media->url],
            ];
        }

        if (empty($imageContents)) {
            return [];
        }

        $response = OpenAI::chat()->create([
            'model'    => 'gpt-4o',
            'messages' => [
                [
                    'role'    => 'user',
                    'content' => array_merge(
                        [
                            [
                                'type' => 'text',
                                'text' => "You are a furniture cataloguing expert. Analyse this furniture product and return relevant style, material, and category tags. Return ONLY a valid JSON array of tag strings. Example: [\"Modern\",\"Leather\",\"Sofa\",\"Minimalist\",\"Living Room\"]",
                            ],
                        ],
                        $imageContents
                    ),
                ],
            ],
            'max_tokens' => 200,
        ]);

        $content = $response->choices[0]->message->content ?? '[]';
        $content = trim(preg_replace('/^```json\s*|```\s*$/m', '', $content));
        $tags    = json_decode($content, true);

        if (! is_array($tags)) {
            return [];
        }

        // Sync tags to product (create if not exist)
        $tagModels = collect($tags)->map(function ($tagName) {
            return Tag::firstOrCreate(
                ['name' => $tagName],
                ['slug' => \Illuminate\Support\Str::slug($tagName)]
            );
        });
        $product->tags()->sync($tagModels->pluck('id'));

        return $tags;
    }
}
