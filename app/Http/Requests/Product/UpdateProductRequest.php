<?php

namespace App\Http\Requests\Product;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool { return true; }

    public function rules(): array
    {
        return [
            'name'        => ['sometimes', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'price'       => ['sometimes', 'numeric', 'min:0'],
            'stock'       => ['sometimes', 'integer', 'min:0'],
            'category_id' => ['sometimes', 'exists:categories,id'],
            'is_active'   => ['boolean'],
            'weight'      => ['nullable', 'numeric', 'min:0'],
            'dimensions'  => ['nullable', 'string', 'max:100'],
            'material'    => ['nullable', 'string', 'max:100'],
            'tags'        => ['array'],
            'tags.*'      => ['exists:tags,id'],
        ];
    }
}
