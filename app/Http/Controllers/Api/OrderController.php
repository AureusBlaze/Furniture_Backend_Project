<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Order\StoreOrderRequest;
use App\Http\Requests\Order\UpdateStatusRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * @group Orders
 */
class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService) {}

    /**
     * List authenticated user's orders.
     *
     * @authenticated
     * @response 200 {"data":[...],"meta":{}}
     */
    public function index(Request $request): JsonResponse
    {
        $orders = $this->orderService->listForUser($request->user());

        return response()->json(OrderResource::collection($orders)->response()->getData(true));
    }

    /**
     * Show an order.
     *
     * @authenticated
     * @urlParam id integer required Order ID. Example: 1
     * @response 200 {"data":{}}
     */
    public function show(Order $order): JsonResponse
    {
        $this->authorize('view', $order);
        $order->load(['items.product', 'addresses', 'user']);

        return response()->json(['data' => new OrderResource($order)]);
    }

    /**
     * Place a new order.
     *
     * @authenticated
     * @bodyParam address_id integer required Delivery address ID. Example: 1
     * @bodyParam items array required Array of order items.
     * @bodyParam items[].product_id integer required Example: 1
     * @bodyParam items[].quantity integer required Min 1. Example: 2
     * @bodyParam notes string Optional order notes. Example: Please deliver after 5pm.
     *
     * @response 201 {"data":{}}
     */
    public function store(StoreOrderRequest $request): JsonResponse
    {
        $order = $this->orderService->create($request->user(), $request->validated());

        return response()->json(['data' => new OrderResource($order)], 201);
    }

    /**
     * Update order status (admin/vendor only in production).
     *
     * @authenticated
     * @urlParam id integer required Order ID. Example: 1
     * @bodyParam status string required One of: pending|paid|processing|shipped|delivered|cancelled. Example: shipped
     *
     * @response 200 {"data":{}}
     */
    public function updateStatus(UpdateStatusRequest $request, Order $order): JsonResponse
    {
        $order = $this->orderService->updateStatus($order, $request->validated()['status']);

        return response()->json(['data' => new OrderResource($order)]);
    }
}
