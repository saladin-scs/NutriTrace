<?php

namespace App\Http\Requests\Shipments;

use Illuminate\Foundation\Http\FormRequest;

class StoreShipmentRequest extends FormRequest
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
            'distribution_channel_id' => ['nullable', 'exists:distribution_channels,id'],
            'distribution_id' => ['nullable', 'exists:distributions,id'],
            'vehicle_id' => ['nullable', 'exists:vehicles,id'],
            'route_id' => ['nullable', 'exists:routes,id'],
            'from_organization_id' => ['required', 'exists:organizations,id'],
            'to_organization_id' => ['required', 'exists:organizations,id', 'different:from_organization_id'],
            'from_location_id' => ['nullable', 'exists:locations,id'],
            'to_location_id' => ['nullable', 'exists:locations,id'],
            'origin_node_id' => ['nullable', 'exists:distribution_nodes,id'],
            'destination_node_id' => ['nullable', 'exists:distribution_nodes,id'],
            'unit' => ['nullable', 'string', 'max:30'],
            'load_kg' => ['nullable', 'numeric', 'min:0'],
            'distance_km' => ['nullable', 'numeric', 'min:0'],
            'eta_at' => ['nullable', 'date'],
            'current_temperature_c' => ['nullable', 'numeric'],
            'notes' => ['nullable', 'string'],
            'items' => ['required', 'array', 'min:1'],
            'items.*.batch_id' => ['required', 'exists:batches,id'],
            'items.*.quantity' => ['nullable', 'numeric', 'min:0.001'],
            'items.*.unit' => ['nullable', 'string', 'max:30'],
        ];
    }
}
