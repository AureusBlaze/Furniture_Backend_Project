<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\MediaService;
use App\Services\ProductService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Products
 */
class ProductController extends Controller
{
    public function __construct(
        private readonly ProductService $productService,
        private readonly MediaService $mediaService,
    ) {}

    /**
     * List products with filtering.
     *
     * @queryParam search string Search by name/description. Example: sofa
     * @queryParam category_id integer Filter by category. Example: 1
     * @queryParam tags[] array Filter by tag IDs or slugs. Example: [1,2]
     * @queryParam min_price number Filter by minimum price. Example: 100
     * @queryParam max_price number Filter by maximum price. Example: 2000
     * @queryParam vendor_id integer Filter by vendor. Example: 1
     * @queryParam sort_by string Sort field (price|name|created_at|stock). Example: price
     * @queryParam sort_direction string asc or desc. Example: asc
     * @queryParam per_page integer Items per page. Example: 15
     *
     * @response 200 {"data":[...],"links":{},"meta":{}}
     */
    public function index(Request $request): JsonResponse
    {
        $products = $this->productService->list($request->all());

        return response()->json(ProductResource::collection($products)->response()->getData(true));
    }

    /**
     * Get a single product.
     *
     * @urlParam id integer required Product ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['vendor', 'category', 'tags', 'media', 'arModel']);

        return response()->json(['data' => new ProductResource($product)]);
    }

    /**
     * Create a product.
     *
     * @authenticated
     * @response 201 {"data":{}}
     */
    public function store(StoreProductRequest $request): JsonResponse
    {
        $data    = $request->validated();
        $product = $this->productService->store($data);

        // Handle media uploads
        if ($request->hasFile('media')) {
            foreach ($request->file('media') as $index => $file) {
                $media = $this->mediaService->store($file);
                $product->media()->attach($media->id, [
                    'is_primary'  => $index === 0,
                    'sort_order'  => $index,
                ]);
            }
        }

        return response()->json(['data' => new ProductResource($product)], 201);
    }

    /**
     * Update a product.
     *
     * @authenticated
     * @urlParam id integer required Product ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function update(UpdateProductRequest $request, Product $product): JsonResponse
    {
        $product = $this->productService->update($product, $request->validated());

        return response()->json(['data' => new ProductResource($product)]);
    }

    /**
     * Delete a product.
     *
     * @authenticated
     * @urlParam id integer required Product ID. Example: 1
     * @response 200 {"message":"Product deleted."}
     */
    public function destroy(Product $product): JsonResponse
    {
        $this->productService->delete($product);

        return response()->json(['message' => 'Product deleted.']);
    }
}
