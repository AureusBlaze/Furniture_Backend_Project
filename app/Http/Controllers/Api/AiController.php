<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Ai\RoomAnalysisRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\AiService;
use Illuminate\Http\JsonResponse;
use Log;

/**
 * @group AI
 */
class AiController extends Controller
{
    public function __construct(private readonly AiService $aiService) {}

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
        Log::info('test 1', ['data' => $aiResponse]);

        // 2. Extract & filter tags from the AI response
        $tags = $this->aiService->extractTagsFromAiResponse($aiResponse);
        Log::info('test 2', ['data' => $tags]);

        // 3. Fallback: if the AI server returned nothing, try Gemini directly
        if (empty($tags)) {
            Log::info('if is true');
            $tags = $this->aiService->analyzeRoomImage($request->file('image'));
        }

        // 4. Query local DB for matching products
        $products = $this->aiService->getSuggestedProducts($tags);
        Log::info('test 3', ['data' => $products]);

        return response()->json([
            'detected_tags' => $tags,
            'suggestions'   => ProductResource::collection($products),
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
            'product'       => new ProductResource($product->load(['tags', 'media'])),
        ]);
    }
}
