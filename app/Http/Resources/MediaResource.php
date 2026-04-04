<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class MediaResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id'         => $this->id,
            'name'       => $this->name,
            'url'        => $this->url ? asset('storage/' . $this->url) : null,
            'type'       => $this->type,
            'size'       => $this->size,
            'mime_type'  => $this->mime_type,
            'is_primary' => $this->pivot?->is_primary ?? false,
            'sort_order' => $this->pivot?->sort_order ?? 0,
        ];
    }
}
