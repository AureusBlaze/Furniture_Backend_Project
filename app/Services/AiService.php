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
    protected string $aiServerUrl;

    /**
     * Stop words to filter from AI-generated tags.
     */
    protected const STOP_WORDS = [
        'a',
        'an',
        'the',
        'and',
        'or',
        'but',
        'with',
        'without',
        'it',
        'its',
        'is',
        'are',
        'was',
        'were',
        'be',
        'been',
        'has',
        'have',
        'had',
        'do',
        'does',
        'did',
        'will',
        'would',
        'could',
        'should',
        'may',
        'might',
        'can',
        'shall',
        'in',
        'on',
        'at',
        'to',
        'for',
        'of',
        'from',
        'by',
        'up',
        'out',
        'off',
        'over',
        'under',
        'again',
        'then',
        'once',
        'here',
        'there',
        'when',
        'where',
        'why',
        'how',
        'all',
        'each',
        'every',
        'both',
        'few',
        'more',
        'most',
        'other',
        'some',
        'such',
        'no',
        'nor',
        'not',
        'only',
        'own',
        'same',
        'so',
        'than',
        'too',
        'very',
        'just',
        'that',
        'this',
        'these',
        'those',
        'what',
        'which',
        'who',
        'about',
        'into',
        'through',
        'during',
        'before',
        'after',
        'above',
        'below',
        'between',
        'if',
        'also',
        'as',
    ];

    public function __construct()
    {
        $this->apiKey = config('gemini.api_key');
        $this->model = config('gemini.model', 'gemini-2.0-flash');
        $this->timeout = config('gemini.request_timeout', 60);
        $this->aiServerUrl = rtrim(config('gemini.ai_server_url', 'http://127.0.0.1:8001'), '/');
    }

    /**
     * Send a room image to the Python AI server for analysis.
     *
     * Returns the full JSON response from the AI server containing
     * room_type, materials, mood, furniture_present, etc.
     */
    public function analyzeRoomViaAiServer(UploadedFile $image): array
    {
        try {
            $response = Http::timeout($this->timeout)
                ->attach('file', file_get_contents($image->getRealPath()), $image->getClientOriginalName())
                ->post("{$this->aiServerUrl}/analyze");

            if ($response->failed()) {
                Log::error('AI Server request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                return [];
            }

            $data = $response->json();

            return $data ?? [];
        } catch (\Exception $e) {
            Log::error('AI Server request exception', [
                'message' => $e->getMessage(),
            ]);
            return [];
        }
    }

    /**
     * Extract meaningful tag strings from the AI server response
     * and filter out stop words.
     *
     * Collects tags from: room_type, materials, mood, furniture_present,
     * and recommended_furniture[].tags.
     *
     * @return string[]
     */
    public function extractTagsFromAiResponse(array $aiResponse): array
    {
        // Unwrap the 'data' key if present (makes method flexible)
        if (isset($aiResponse['data']) && is_array($aiResponse['data'])) {
            $aiResponse = $aiResponse['data'];
        }

        $tags = [];

        // Helper to split a phrase into words (lowercase, filter short words later)
        $splitIntoWords = function ($phrase) {
            $words = preg_split('/[\s]+/', trim($phrase));
            return array_filter($words, fn($w) => strlen($w) > 1);
        };

        // Helper to get the first word of a phrase
        $firstWord = function ($phrase) {
            $words = preg_split('/[\s]+/', trim($phrase));
            return $words[0] ?? '';
        };

        // Process dominant_colors: add color names as whole tags
        if (!empty($aiResponse['dominant_colors']['colors']) && is_array($aiResponse['dominant_colors']['colors'])) {
            foreach ($aiResponse['dominant_colors']['colors'] as $color) {
                if (!empty($color['name']) && is_string($color['name'])) {
                    $tags[] = strtolower($color['name']);
                }
            }
        }

        // Process lighting_analysis: prefix "light" to each string value
        if (!empty($aiResponse['lighting_analysis']) && is_array($aiResponse['lighting_analysis'])) {
            foreach ($aiResponse['lighting_analysis'] as $value) {
                if (is_string($value) && strlen($value) > 0) {
                    $tags[] = 'light ' . strtolower($value);
                }
            }
        }

        // Helper to extract from confidence arrays with optional mode
        $extractFromConfidenceArray = function ($array, $mode = 'split') use (&$tags, $splitIntoWords, $firstWord) {
            if (empty($array) || !is_array($array))
                return;

            // Find item with highest confidence
            $best = null;
            $bestConf = -1;
            foreach ($array as $item) {
                if (isset($item['confidence']) && $item['confidence'] > $bestConf) {
                    $bestConf = $item['confidence'];
                    $best = $item;
                }
            }
            if ($best && isset($best['label']) && is_string($best['label'])) {
                if ($mode === 'first_word') {
                    $word = $firstWord($best['label']);
                    if (!empty($word)) {
                        $tags[] = strtolower($word);
                    }
                } else {
                    // split into all words
                    $words = $splitIntoWords($best['label']);
                    foreach ($words as $word) {
                        $tags[] = strtolower($word);
                    }
                }
            }
        };

        // Process room_type and furniture_present – split all words
        if (!empty($aiResponse['room_type'])) {
            $extractFromConfidenceArray($aiResponse['room_type'], 'split');
        }
        if (!empty($aiResponse['furniture_present'])) {
            $extractFromConfidenceArray($aiResponse['furniture_present'], 'split');
        }

        // Process materials and mood – take only the first word
        if (!empty($aiResponse['materials'])) {
            $extractFromConfidenceArray($aiResponse['materials'], 'first_word');
        }
        if (!empty($aiResponse['mood'])) {
            $extractFromConfidenceArray($aiResponse['mood'], 'first_word');
        }

        // Process room_analysis: split all string values into words
        if (!empty($aiResponse['room_analysis']) && is_array($aiResponse['room_analysis'])) {
            foreach ($aiResponse['room_analysis'] as $value) {
                if (is_string($value)) {
                    $words = $splitIntoWords($value);
                    foreach ($words as $word) {
                        $tags[] = strtolower($word);
                    }
                }
            }
        }

        // Process summary if present
        if (!empty($aiResponse['summary']) && is_array($aiResponse['summary'])) {
            foreach ($aiResponse['summary'] as $value) {
                if (is_string($value)) {
                    $words = $splitIntoWords($value);
                    foreach ($words as $word) {
                        $tags[] = strtolower($word);
                    }
                }
            }
        }

        // Remove duplicates
        $uniqueTags = array_unique($tags);

        // Filter stop words (your existing method)
        return $this->filterStopWords($uniqueTags);
    }

    /**
     * Filter out stop words from a tag array.
     *
     * @param  string[] $tags
     * @return string[]
     */
    protected function filterStopWords(array $tags): array
    {
        $stopWords = self::STOP_WORDS;

        return collect($tags)
            ->map(fn($tag) => trim((string) $tag))
            ->filter(fn($tag) => $tag !== '' && strlen($tag) > 1)
            ->filter(fn($tag) => !in_array(strtolower($tag), $stopWords, true))
            ->unique(fn($tag) => strtolower($tag))
            ->values()
            ->all();
    }

    /**
     * Analyse a room image via Gemini and return an array of furniture tag strings.
     * (Kept as fallback if the AI server is unavailable.)
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
