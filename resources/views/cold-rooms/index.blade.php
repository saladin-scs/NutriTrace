@extends('layouts.front')

@section('title', 'Chambres froides & flux')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="nt-kicker">Canal de distribution</p>
        <h1 class="nt-page-title mt-1">Chambres froides & traçabilité des flux</h1>
        <p class="nt-page-sub">Nœuds physiques : entrées, sorties, acteurs, conditions, durées — historisés.</p>
    </div>
    @can('create', App\Models\ColdRoom::class)
        <a href="{{ route('cold-rooms.create') }}" class="nt-btn">Nouvelle chambre</a>
    @endcan
</div>

<div class="mt-8 grid gap-4">
    @forelse ($rooms as $room)
        <a href="{{ route('cold-rooms.show', $room) }}" class="nt-card block transition hover:-translate-y-0.5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ $room->name }}</h2>
                    <p class="mt-1 text-sm text-nt-ink/60">
                        {{ $room->code }} · {{ $room->organization?->name }}
                        @if ($room->location?->city) · {{ $room->location->city }} @endif
                        · {{ $room->movements_count }} flux
                        · {{ $room->open_storage_records_count }} lots en stock
                    </p>
                    <p class="mt-1 text-xs text-nt-ink/45">
                        Occupation {{ $room->occupancyRate() }}%
                        ({{ number_format((float) $room->occupied_capacity_kg, 0, ',', ' ') }} /
                        {{ number_format((float) $room->capacity_kg, 0, ',', ' ') }} kg)
                        @if ($room->current_temperature_c !== null)
                            · {{ $room->current_temperature_c }}°C
                        @endif
                    </p>
                </div>
                <span class="rounded-full bg-nt-mist px-3 py-1 text-xs font-medium">{{ $room->status->label() }}</span>
            </div>
        </a>
    @empty
        <div class="nt-card text-sm text-nt-ink/70">
            Aucune chambre froide. Créez un nœud pour historiser les flux de lots.
        </div>
    @endforelse
</div>
<div class="mt-6">{{ $rooms->links() }}</div>
@endsection
