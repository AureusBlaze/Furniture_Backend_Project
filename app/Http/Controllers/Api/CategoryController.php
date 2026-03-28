<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Categories
 */
class CategoryController extends Controller
{
    /**
     * List all top-level categories with children.
     *
     * @response 200 {"data":[{"id":1,"name":"Living Room","children":[...]}]}
     */
    public function index(): JsonResponse
    {
        $categories = Category::topLevel()->with('children')->withCount('products')->get();

        return response()->json(['data' => CategoryResource::collection($categories)]);
    }

    /**
     * Show a category.
     *
     * @urlParam id integer required Category ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function show(Category $category): JsonResponse
    {
        $category->load('children', 'parent');

        return response()->json(['data' => new CategoryResource($category)]);
    }

    /**
     * Create a category.
     *
     * @authenticated
     * @bodyParam name string required Example: Living Room
     * @bodyParam parent_id integer nullable Parent category ID. Example: null
     * @response 201 {"data":{}}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'image_url'   => ['nullable', 'url'],
        ]);
        $data['slug'] = Str::slug($data['name']) . '-' . uniqid();

        $category = Category::create($data);

        return response()->json(['data' => new CategoryResource($category)], 201);
    }

    /**
     * Update a category.
     *
     * @authenticated
     * @urlParam id integer required Category ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function update(Request $request, Category $category): JsonResponse
    {
        $data = $request->validate([
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'parent_id'   => ['nullable', 'exists:categories,id'],
            'image_url'   => ['nullable', 'url'],
        ]);

        if (isset($data['name'])) {
            $data['slug'] = Str::slug($data['name']) . '-' . uniqid();
        }

        $category->update($data);

        return response()->json(['data' => new CategoryResource($category)]);
    }

    /**
     * Delete a category.
     *
     * @authenticated
     * @urlParam id integer required Category ID. Example: 1
     * @response 200 {"message":"Category deleted."}
     */
    public function destroy(Category $category): JsonResponse
    {
        $category->delete();

        return response()->json(['message' => 'Category deleted.']);
    }
}
