<?php

namespace App\Http\Requests\Products;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'category_id' => ['nullable', 'integer', Rule::exists('product_categories', 'id')],
            'name' => ['required', 'string', 'max:180'],
            'sku' => ['nullable', 'string', 'max:80'],
            'description' => ['nullable', 'string', 'max:5000'],
            'unit' => ['nullable', 'string', 'max:30'],
            'packaging_type' => ['nullable', 'string', 'max:80'],
            'origin_country' => ['nullable', 'string', 'size:2'],
            'status' => ['nullable', Rule::in(['draft', 'active', 'archived'])],
        ];
    }
}
