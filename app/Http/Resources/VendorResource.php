<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class VendorResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'          => $this->id,
            'name'        => $this->name,
            'description' => $this->description,
            'logo_url'    => $this->logo_url ? asset('storage/' . $this->logo_url) : null,
            'is_active'   => $this->is_active,
            'address'     => new AddressResource($this->whenLoaded('address')),
            'created_at'  => $this->created_at->toISOString(),
        ];
    }
}
