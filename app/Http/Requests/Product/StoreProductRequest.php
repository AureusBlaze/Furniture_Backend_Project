<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['required', 'numeric', 'min:0'],
            'stock'       => ['required', 'integer', 'min:0'],
            'category_id' => ['required', 'exists:categories,id'],
            'vendor_id'   => ['required', 'exists:vendors,id'],
            'is_active'   => ['boolean'],
            'weight'      => ['nullable', 'numeric', 'min:0'],
            'dimensions'  => ['nullable', 'string', 'max:100'],
            'material'    => ['nullable', 'string', 'max:100'],
            'tags'        => ['array'],
            'tags.*'      => ['exists:tags,id'],
            'media'       => ['array'],
            'media.*'     => ['file', 'mimes:jpg,jpeg,png,webp,mp4', 'max:20480'],
        ];
    }
}
