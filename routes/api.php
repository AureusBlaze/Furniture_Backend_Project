<?php

use App\Http\Controllers\Api\AiController;
use App\Http\Controllers\Api\ArModelController;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\ProfileController;
use App\Http\Controllers\Api\TagController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

// ── Public Routes ─────────────────────────────────────────────────────────

// Auth
Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login',    [AuthController::class, 'login']);
});

// Public Product browsing
Route::get('products',          [ProductController::class, 'index']);
Route::get('products/{product}', [ProductController::class, 'show']);

// Product AR model (public)
Route::get('products/{product}/ar-model', [ArModelController::class, 'show']);

// Categories (public)
Route::apiResource('categories', CategoryController::class)->only(['index', 'show']);

// Tags (public)
Route::get('tags',       [TagController::class, 'index']);
Route::get('tags/{tag}', [TagController::class, 'show']);

// ── Authenticated Routes (Sanctum) ────────────────────────────────────────

Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
        Route::get('me',      [AuthController::class, 'me']);
    });

    // Profile
    Route::get('profile',  [ProfileController::class, 'show']);
    Route::put('profile',  [ProfileController::class, 'update']);

    // Products (write)
    Route::post('products',           [ProductController::class, 'store']);
    Route::put('products/{product}',  [ProductController::class, 'update']);
    Route::patch('products/{product}',[ProductController::class, 'update']);
    Route::delete('products/{product}',[ProductController::class, 'destroy']);

    // Categories (write)
    Route::apiResource('categories', CategoryController::class)->only(['store', 'update', 'destroy']);

    // Tags (write)
    Route::post('tags',         [TagController::class, 'store']);
    Route::delete('tags/{tag}', [TagController::class, 'destroy']);

    // Orders
    Route::get('orders',                          [OrderController::class, 'index']);
    Route::get('orders/{order}',                  [OrderController::class, 'show']);
    Route::post('orders',                         [OrderController::class, 'store']);
    Route::patch('orders/{order}/status',         [OrderController::class, 'updateStatus']);

    // AR Models (write)
    Route::get('ar-models',                       [ArModelController::class, 'index']);
    Route::post('products/{product}/ar-model',    [ArModelController::class, 'store']);
    Route::delete('ar-models/{arModel}',          [ArModelController::class, 'destroy']);

    // AI
    Route::post('ai/suggest',                     [AiController::class, 'suggest']);
    Route::post('ai/tag-product/{product}',       [AiController::class, 'tagProduct']);
});
