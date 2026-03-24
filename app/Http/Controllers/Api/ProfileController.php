<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Profile\UpdateProfileRequest;
use App\Http\Resources\UserResource;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

/**
 * @group Profile
 */
class ProfileController extends Controller
{
    /**
     * Get authenticated user profile.
     *
     * @authenticated
     * @response 200 {"data":{}}
     */
    public function show(Request $request): JsonResponse
    {
        $user = $request->user()->load(['defaultAddress', 'vendor', 'addresses']);

        return response()->json(['data' => new UserResource($user)]);
    }

    /**
     * Update authenticated user profile.
     *
     * @authenticated
     * @bodyParam name string optional Example: Jane Doe
     * @bodyParam email string optional Example: jane@example.com
     * @bodyParam password string optional New password (min 8). Example: newpassword
     * @bodyParam password_confirmation string optional Must match password.
     * @response 200 {"data":{}}
     */
    public function update(UpdateProfileRequest $request): JsonResponse
    {
        $data = $request->validated();

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        $request->user()->update($data);

        return response()->json(['data' => new UserResource($request->user()->fresh())]);
    }
}
