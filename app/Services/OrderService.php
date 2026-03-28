<?php

namespace App\Services;

use App\Models\Address;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function listForUser(User $user): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Order::with(['items.product', 'addresses'])
            ->forUser($user->id)
            ->latest()
            ->paginate(10);
    }

    public function create(User $user, array $data): Order
    {
        return DB::transaction(function () use ($user, $data) {
            // Create the address and attach to user
            $address = Address::create([
                'user_id'      => $user->id,
                'address_line' => $data['address_line'],
                'city'         => $data['city'],
                'country'      => $data['country'],
                'postal_code'  => $data['postal_code'],
                'is_default'   => $user->addresses()->count() === 0,
            ]);

            // Set as user's default if they don't have one
            if ($user->default_address_id === null) {
                $user->update(['default_address_id' => $address->id]);
            }

            // Validate products exist and have stock
            $itemsData = [];
            $total     = 0;

            foreach ($data['items'] as $itemInput) {
                $product = Product::findOrFail($itemInput['product_id']);

                if ($product->stock < $itemInput['quantity']) {
                    throw new \InvalidArgumentException("Insufficient stock for product: {$product->name}");
                }

                $subtotal    = $product->price * $itemInput['quantity'];
                $total      += $subtotal;
                $itemsData[] = [
                    'product'    => $product,
                    'quantity'   => $itemInput['quantity'],
                    'unit_price' => $product->price,
                    'subtotal'   => $subtotal,
                ];
            }

            // Create order
            $order = Order::create([
                'user_id'     => $user->id,
                'total_price' => $total,
                'status'      => 'pending',
                'notes'       => $data['notes'] ?? null,
            ]);

            // Order items & decrement stock
            foreach ($itemsData as $item) {
                OrderItem::create([
                    'order_id'   => $order->id,
                    'product_id' => $item['product']->id,
                    'quantity'   => $item['quantity'],
                    'unit_price' => $item['unit_price'],
                    'subtotal'   => $item['subtotal'],
                ]);
                $item['product']->decrement('stock', $item['quantity']);
            }

            // Attach address via pivot
            $order->addresses()->attach($address->id);

            return $order->load(['items.product', 'addresses']);
        });
    }

    public function updateStatus(Order $order, string $status): Order
    {
        $timestamps = match ($status) {
            'paid'      => ['paid_at'      => now()],
            'shipped'   => ['shipped_at'   => now()],
            'delivered' => ['delivered_at' => now()],
            default     => [],
        };

        $order->update(array_merge(['status' => $status], $timestamps));

        return $order;
    }
}
