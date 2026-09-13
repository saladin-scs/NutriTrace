<?php

namespace App\Http\Requests\Batches;

use App\Enums\TransportMode;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordDistributionRequest extends FormRequest
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
            'to_organization_id' => ['required', 'integer', Rule::exists('organizations', 'id')],
            'quantity' => ['nullable', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:30'],
            'transport_mode' => ['nullable', Rule::enum(TransportMode::class)],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'shipped_at' => ['nullable', 'date'],
            'received_at' => ['nullable', 'date'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
