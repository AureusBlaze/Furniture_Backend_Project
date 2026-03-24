<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Resources\TagResource;
use App\Models\Tag;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

/**
 * @group Tags
 */
class TagController extends Controller
{
    /**
     * List all tags.
     *
     * @response 200 {"data":[{"id":1,"name":"Modern","slug":"modern"}]}
     */
    public function index(): JsonResponse
    {
        return response()->json(['data' => TagResource::collection(Tag::all())]);
    }

    /**
     * Show a tag.
     *
     * @urlParam id integer required Tag ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function show(Tag $tag): JsonResponse
    {
        return response()->json(['data' => new TagResource($tag)]);
    }

    /**
     * Create a tag.
     *
     * @authenticated
     * @bodyParam name string required Example: Minimalist
     * @response 201 {"data":{}}
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100', 'unique:tags,name'],
        ]);
        $data['slug'] = Str::slug($data['name']);

        $tag = Tag::create($data);

        return response()->json(['data' => new TagResource($tag)], 201);
    }

    /**
     * Delete a tag.
     *
     * @authenticated
     * @urlParam id integer required Tag ID. Example: 1
     * @response 200 {"message":"Tag deleted."}
     */
    public function destroy(Tag $tag): JsonResponse
    {
        $tag->delete();

        return response()->json(['message' => 'Tag deleted.']);
    }
}
