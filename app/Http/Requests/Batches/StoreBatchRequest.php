<?php

namespace App\Http\Requests\Batches;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreBatchRequest extends FormRequest
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
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:30'],
            'produced_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'production_location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
