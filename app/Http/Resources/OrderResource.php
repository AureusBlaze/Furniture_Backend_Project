<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'status'       => $this->status,
            'total_price'  => (float) $this->total_price,
            'notes'        => $this->notes,
            'user'         => new UserResource($this->whenLoaded('user')),
            'items'        => OrderItemResource::collection($this->whenLoaded('items')),
            'addresses'    => AddressResource::collection($this->whenLoaded('addresses')),
            'paid_at'      => $this->paid_at?->toISOString(),
            'shipped_at'   => $this->shipped_at?->toISOString(),
            'delivered_at' => $this->delivered_at?->toISOString(),
            'created_at'   => $this->created_at->toISOString(),
        ];
    }
}
