<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'              => $this->id,
            'name'            => $this->name,
            'email'           => $this->email,
            'role'            => $this->role,
            'default_address' => new AddressResource($this->whenLoaded('defaultAddress')),
            'vendor'          => new VendorResource($this->whenLoaded('vendor')),
            'created_at'      => $this->created_at->toISOString(),
        ];
    }
}
