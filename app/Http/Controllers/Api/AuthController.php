<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Authentication
 */
class AuthController extends Controller
{
    public function __construct(private readonly AuthService $authService) {}

    /**
     * Register a new user.
     *
     * @bodyParam name string required User's full name. Example: John Doe
     * @bodyParam email string required User's email. Example: john@example.com
     * @bodyParam password string required Min 8 characters. Example: password123
     * @bodyParam password_confirmation string required Must match password. Example: password123
     * @bodyParam role string Optional: customer or vendor. Example: customer
     *
     * @response 201 {"user":{"id":1,"name":"John Doe","email":"john@example.com","role":"customer"},"token":"1|abc123..."}
     */
    public function register(RegisterRequest $request): JsonResponse
    {
        $result = $this->authService->register($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ], 201);
    }

    /**
     * Login.
     *
     * @bodyParam email string required Example: john@example.com
     * @bodyParam password string required Example: password123
     *
     * @response 200 {"user":{},"token":"1|abc123"}
     * @response 422 {"message":"The provided credentials are incorrect."}
     */
    public function login(LoginRequest $request): JsonResponse
    {
        $result = $this->authService->login($request->validated());

        return response()->json([
            'user'  => new UserResource($result['user']),
            'token' => $result['token'],
        ]);
    }

    /**
     * Logout (revoke current token).
     *
     * @authenticated
     * @response 200 {"message":"Logged out successfully."}
     */
    public function logout(Request $request): JsonResponse
    {
        $this->authService->logout($request->user());

        return response()->json(['message' => 'Logged out successfully.']);
    }

    /**
     * Get authenticated user.
     *
     * @authenticated
     * @response 200 {"user":{}}
     */
    public function me(Request $request): JsonResponse
    {
        $user = $request->user()->load(['defaultAddress', 'vendor']);

        return response()->json(['user' => new UserResource($user)]);
    }
}
