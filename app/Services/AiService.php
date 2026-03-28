<?php

namespace App\Services;

use App\Models\Product;
use App\Models\Tag;
use Illuminate\Support\Collection;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AiService
{
    protected string $apiKey;
    protected string $model;
    protected int $timeout;

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key');
        $this->model = config('gemini.model', 'gemini-2.0-flash');
        $this->timeout = config('gemini.request_timeout', 60);
    }

    /**
     * Analyse a room image and return an array of furniture tag strings.
     *
     * @return string[]
     */
    public function analyzeRoomImage(UploadedFile $image): array
    {
        $imageData = base64_encode(file_get_contents($image->getRealPath()));
        $mimeType = $image->getMimeType();

        $response = $this->callGemini(
            prompt: 'You are a furniture expert. Analyse this room image and identify all furniture items and their style attributes (e.g. "Modern", "Sofa", "Wooden", "Minimalist", "Dining Table"). Return ONLY a valid JSON array of tag strings, no explanation. Example: ["Modern","Sofa","Wooden","Coffee Table","Minimalist"]',
            images: [
                [
                    'mime_type' => $mimeType,
                    'data' => $imageData,
                ],
            ],
            maxTokens: 300
        );

        Log::info('Gemini API response', [
            'response' => $response,
        ]);

        return $this->parseJsonArray($response);
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
     * Auto-tag a product using its images via Gemini Vision.
     *
     * @return string[]
     */
    public function autoTagProduct(Product $product): array
    {
        $product->load('media');

        // Build image parts from up to 3 product images
        $images = [];
        foreach ($product->media->take(3) as $media) {
            // For remote URLs, fetch and encode the image
            try {
                $imageContent = file_get_contents($media->url);
                if ($imageContent === false) {
                    continue;
                }

                // Detect MIME type from content
                $finfo = new \finfo(FILEINFO_MIME_TYPE);
                $mimeType = $finfo->buffer($imageContent);

                $images[] = [
                    'mime_type' => $mimeType,
                    'data' => base64_encode($imageContent),
                ];
            } catch (\Exception $e) {
                Log::warning("AiService: Failed to fetch product image: {$media->url}", [
                    'error' => $e->getMessage(),
                ]);
                continue;
            }
        }

        if (empty($images)) {
            return [];
        }

        $response = $this->callGemini(
            prompt: 'You are a furniture cataloguing expert. Analyse this furniture product and return relevant style, material, and category tags. Return ONLY a valid JSON array of tag strings. Example: ["Modern","Leather","Sofa","Minimalist","Living Room"]',
            images: $images,
            maxTokens: 200
        );

        $tags = $this->parseJsonArray($response);

        if (empty($tags)) {
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

    /**
     * Make a request to the Gemini API.
     *
     * @param  string  $prompt     The text prompt
     * @param  array   $images     Array of ['mime_type' => ..., 'data' => base64...]
     * @param  int     $maxTokens  Maximum output tokens
     * @return string  The text response from Gemini
     */
    protected function callGemini(string $prompt, array $images = [], int $maxTokens = 300): string
    {
        $parts = [];

        // Add text prompt
        $parts[] = ['text' => $prompt];

        // Add image parts (inline_data for base64-encoded images)
        foreach ($images as $image) {
            $parts[] = [
                'inline_data' => [
                    'mime_type' => $image['mime_type'],
                    'data' => $image['data'],
                ],
            ];
        }

        $payload = [
            'contents' => [
                [
                    'parts' => $parts,
                ],
            ],
            'generationConfig' => [
                'maxOutputTokens' => $maxTokens,
                'temperature' => 0.2,
            ],
        ];

        $url = "https://generativelanguage.googleapis.com/v1beta/models/{$this->model}:generateContent";

        $response = Http::timeout($this->timeout)
            ->withQueryParameters(['key' => $this->apiKey])
            ->post($url, $payload);

        if ($response->failed()) {
            Log::error('Gemini API request failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return '[]';
        }

        $data = $response->json();

        // Extract text from Gemini response structure
        return $data['candidates'][0]['content']['parts'][0]['text'] ?? '[]';
    }

    /**
     * Parse a JSON array from the AI response, handling markdown fences.
     *
     * @return string[]
     */
    protected function parseJsonArray(string $content): array
    {
        // Strip markdown code fences if present
        $content = trim(preg_replace('/^```json\s*|```\s*$/m', '', $content));

        $tags = json_decode($content, true);

        return is_array($tags) ? $tags : [];
    }
}
