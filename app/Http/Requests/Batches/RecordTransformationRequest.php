<?php

namespace App\Http\Requests\Batches;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordTransformationRequest extends FormRequest
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
            'process_name' => ['required', 'string', 'max:180'],
            'output_product_id' => ['nullable', 'integer', Rule::exists('products', 'id')],
            'output_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'input_quantity' => ['nullable', 'numeric', 'min:0.001'],
            'loss_quantity' => ['nullable', 'numeric', 'min:0'],
            'unit' => ['nullable', 'string', 'max:30'],
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'occurred_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
