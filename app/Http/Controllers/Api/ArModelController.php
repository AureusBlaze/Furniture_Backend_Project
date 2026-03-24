<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\ArModelResource;
use App\Models\ArModel;
use App\Models\Product;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group AR Models
 */
class ArModelController extends Controller
{
    /**
     * List all AR models.
     *
     * @response 200 {"data":[]}
     */
    public function index(): JsonResponse
    {
        $models = ArModel::with('product')->paginate(20);

        return response()->json(ArModelResource::collection($models)->response()->getData(true));
    }

    /**
     * Get AR model for a product.
     *
     * @urlParam product integer required Product ID. Example: 1
     * @response 200 {"data":{}}
     * @response 404 {"message":"No AR model found for this product."}
     */
    public function show(Product $product): JsonResponse
    {
        $arModel = $product->arModel;

        if (! $arModel) {
            return response()->json(['message' => 'No AR model found for this product.'], 404);
        }

        return response()->json(['data' => new ArModelResource($arModel)]);
    }

    /**
     * Attach an AR model to a product.
     *
     * @authenticated
     * @urlParam product integer required Product ID. Example: 1
     * @bodyParam file_url string required URL to the 3D model file. Example: https://example.com/model.glb
     * @bodyParam format string required GLB, USDZ, or OBJ. Example: glb
     * @bodyParam thumbnail_url string optional Thumbnail image URL.
     * @bodyParam file_size integer optional File size in bytes.
     * @response 201 {"data":{}}
     */
    public function store(Request $request, Product $product): JsonResponse
    {
        $data = $request->validate([
            'file_url'      => ['required', 'url'],
            'format'        => ['required', 'in:glb,usdz,obj'],
            'thumbnail_url' => ['nullable', 'url'],
            'file_size'     => ['nullable', 'integer'],
        ]);

        $arModel = ArModel::updateOrCreate(
            ['product_id' => $product->id],
            array_merge($data, ['product_id' => $product->id])
        );

        return response()->json(['data' => new ArModelResource($arModel)], 201);
    }

    /**
     * Delete an AR model.
     *
     * @authenticated
     * @urlParam arModel integer required AR Model ID. Example: 1
     * @response 200 {"message":"AR model deleted."}
     */
    public function destroy(ArModel $arModel): JsonResponse
    {
        $arModel->delete();

        return response()->json(['message' => 'AR model deleted.']);
    }
}
