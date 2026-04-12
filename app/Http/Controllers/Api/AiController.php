<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\RoomAnalysisRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

/**
 * @group AI
 */
class AiController extends Controller
{
    public function __construct(private readonly AiService $aiService)
    {
    }

    /**
     * Suggest products from a room image.
     *
     * Upload a room photo and receive AI-powered furniture product suggestions
     * based on Gemini Vision analysis of the room's style and furniture.
     *
     * @authenticated
     * @bodyParam image file required Room image (jpeg/png/webp, max 10MB).
     *
     * @response 200 {
     *   "detected_tags": ["Modern","Sofa","Wooden","Minimalist"],
     *   "suggestions": {"data":[...]}
     * }
     */
    public function suggest(RoomAnalysisRequest $request): JsonResponse
    {
        // 1. Forward the image to the Python AI server
        $aiResponse = $this->aiService->analyzeRoomViaAiServer($request->file('image'));
        Log::info('AI Server response', ['response' => $aiResponse]);

        // 2. Extract & filter tags from the AI response
        $tags = $this->aiService->extractTagsFromAiResponse($aiResponse);
        Log::info('Extracted tags from AI response', ['tags' => $tags]);

        // 3. Fallback: if the AI server returned nothing, try Gemini directly
        if (empty($tags)) {
            $tags = $this->aiService->analyzeRoomImage($request->file('image'));
        }


        // 4. Build a human-readable description from the AI summary
        $description = '';
        $summary = data_get($aiResponse, 'summary', []);

        if (!empty($summary)) {
            $labelMap = [
                'room_type' => 'room type',
                'primary_style' => 'primary style',
                'color_temperature' => 'color temperature',
                'saturation' => 'saturation',
                'brightness' => 'brightness',
                'mood' => 'mood',
            ];

            $sentences = [];
            foreach ($labelMap as $key => $label) {
                if (!empty($summary[$key])) {
                    $sentences[] = "The {$label} is {$summary[$key]}";
                }
            }

            $description = implode('. ', $sentences) . '.';
        }

        // 5. Query local DB for matching products
        $products = $this->aiService->getSuggestedProducts($tags);

        return response()->json([
            'description' => $description,
            'detected_tags' => $tags,
            'suggestions' => ProductResource::collection($products),
        ]);
    }

    /**
     * Auto-tag a product using AI.
     *
     * Analyses the product's existing images via Gemini Vision and automatically
     * assigns furniture style/material/category tags to the product.
     *
     * @authenticated
     * @urlParam product integer required Product ID. Example: 1
     *
     * @response 200 {
     *   "assigned_tags": ["Modern","Leather","Sofa","Minimalist"],
     *   "product": {}
     * }
     */
    public function tagProduct(Product $product): JsonResponse
    {
        $tags = $this->aiService->autoTagProduct($product);

        return response()->json([
            'assigned_tags' => $tags,
            'product' => new ProductResource($product->load(['tags', 'media'])),
        ]);
    }
}
