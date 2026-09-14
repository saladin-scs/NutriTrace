@extends('layouts.front')

@section('title', 'Nouvelle chambre froide')

@section('content')
<div class="max-w-2xl">
    <h1 class="font-display text-3xl font-semibold">Nouvelle chambre froide</h1>
    <p class="mt-1 text-sm text-nt-ink/60">Nœud du canal de distribution — pas un simple stockage.</p>

    <form method="POST" action="{{ route('cold-rooms.store') }}" class="nt-card mt-6 space-y-4">
        @csrf
        <div>
            <label class="nt-label" for="organization_id">Organisation</label>
            <select id="organization_id" name="organization_id" class="nt-field" required>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}">{{ $organization->name }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label" for="name">Nom</label>
            <input id="name" name="name" class="nt-field" value="{{ old('name') }}" required placeholder="Ex. CF Charguia A">
        </div>
        <div>
            <label class="nt-label" for="type">Type</label>
            <select id="type" name="type" class="nt-field">
                @foreach ($types as $type)
                    <option value="{{ $type->value }}" @selected(old('type', 'refrigerated') === $type->value)>{{ $type->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="capacity_kg">Capacité (kg)</label>
                <input id="capacity_kg" name="capacity_kg" type="number" step="0.001" class="nt-field" value="{{ old('capacity_kg') }}">
            </div>
            <div>
                <label class="nt-label" for="code">Code (optionnel)</label>
                <input id="code" name="code" class="nt-field" value="{{ old('code') }}">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="target_temp_min_c">T° min</label>
                <input id="target_temp_min_c" name="target_temp_min_c" type="number" step="0.1" class="nt-field" value="{{ old('target_temp_min_c', 2) }}">
            </div>
            <div>
                <label class="nt-label" for="target_temp_max_c">T° max</label>
                <input id="target_temp_max_c" name="target_temp_max_c" type="number" step="0.1" class="nt-field" value="{{ old('target_temp_max_c', 4) }}">
            </div>
        </div>
        <div>
            <label class="nt-label" for="description">Description</label>
            <textarea id="description" name="description" rows="2" class="nt-field">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Créer le nœud</button>
            <a href="{{ route('cold-rooms.index') }}" class="nt-btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
