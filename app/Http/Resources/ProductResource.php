<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProductResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'           => $this->id,
            'name'         => $this->name,
            'slug'         => $this->slug,
            'description'  => $this->description,
            'price'        => (float) $this->price,
            'stock'        => $this->stock,
            'is_active'    => $this->is_active,
            'weight'       => $this->weight ? (float) $this->weight : null,
            'dimensions'   => $this->dimensions,
            'material'     => $this->material,
            'vendor'       => new VendorResource($this->whenLoaded('vendor')),
            'category'     => new CategoryResource($this->whenLoaded('category')),
            'tags'         => TagResource::collection($this->whenLoaded('tags')),
            'media'        => MediaResource::collection($this->whenLoaded('media')),
            'has_ar_model' => $this->arModel ? true : false,
            'ar_model'     => new ArModelResource($this->whenLoaded('arModel')),
            'created_at'   => $this->created_at->toISOString(),
            'updated_at'   => $this->updated_at->toISOString(),
        ];
    }
}
