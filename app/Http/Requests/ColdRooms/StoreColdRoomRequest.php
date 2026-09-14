<?php

namespace App\Http\Requests\ColdRooms;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreColdRoomRequest extends FormRequest
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
            'location_id' => ['nullable', 'integer', Rule::exists('locations', 'id')],
            'responsible_user_id' => ['nullable', 'integer', Rule::exists('users', 'id')],
            'name' => ['required', 'string', 'max:180'],
            'code' => ['nullable', 'string', 'max:60', 'unique:cold_rooms,code'],
            'type' => ['nullable', 'string', Rule::in(['positive', 'negative', 'frozen', 'refrigerated'])],
            'capacity_kg' => ['nullable', 'numeric', 'min:0'],
            'target_temp_min_c' => ['nullable', 'numeric'],
            'target_temp_max_c' => ['nullable', 'numeric'],
            'current_temperature_c' => ['nullable', 'numeric'],
            'humidity_min_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'humidity_max_pct' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'energy_kwh_day' => ['nullable', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
