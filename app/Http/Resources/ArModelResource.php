<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ArModelResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'            => $this->id,
            'product_id'    => $this->product_id,
            'file_url'      => $this->file_url,
            'format'        => $this->format,
            'file_size'     => $this->file_size,
            'thumbnail_url' => $this->thumbnail_url,
            'created_at'    => $this->created_at->toISOString(),
        ];
    }
}
