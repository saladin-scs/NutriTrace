<?php

namespace App\Http\Requests\ColdRooms;

use App\Enums\ColdRoomMovementType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RecordColdRoomMovementRequest extends FormRequest
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
            'type' => ['required', Rule::enum(ColdRoomMovementType::class)],
            'batch_id' => ['nullable', 'integer', Rule::exists('batches', 'id')],
            'quantity' => ['required', 'numeric', 'min:0.001'],
            'unit' => ['nullable', 'string', 'max:30'],
            'occurred_at' => ['nullable', 'date'],
            'temperature_c' => ['nullable', 'numeric'],
            'humidity_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'duration_minutes' => ['nullable', 'integer', 'min:0'],
            'from_organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')],
            'to_organization_id' => ['nullable', 'integer', Rule::exists('organizations', 'id')],
            'event_label' => ['nullable', 'string', 'max:180'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
