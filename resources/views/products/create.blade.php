@extends('layouts.front')

@section('title', 'Nouveau produit')

@section('content')
<div class="max-w-2xl">
    <h1 class="font-display text-3xl font-semibold">Nouveau produit</h1>
    <form method="POST" action="{{ route('products.store') }}" class="nt-card mt-6 space-y-4">
        @csrf
        <div>
            <label class="nt-label" for="organization_id">Organisation</label>
            <select id="organization_id" name="organization_id" class="nt-field" required>
                @foreach ($organizations as $organization)
                    <option value="{{ $organization->id }}" @selected(old('organization_id') == $organization->id)>
                        {{ $organization->name }} ({{ $organization->type->label() }})
                    </option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('organization_id')" />
        </div>
        <div>
            <label class="nt-label" for="name">Nom</label>
            <input id="name" name="name" class="nt-field" value="{{ old('name') }}" required>
            <x-input-error :messages="$errors->get('name')" />
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="category_id">Catégorie</label>
                <select id="category_id" name="category_id" class="nt-field">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id') == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="nt-label" for="unit">Unité</label>
                <input id="unit" name="unit" class="nt-field" value="{{ old('unit', 'kg') }}">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="sku">SKU</label>
                <input id="sku" name="sku" class="nt-field" value="{{ old('sku') }}">
            </div>
            <div>
                <label class="nt-label" for="packaging_type">Emballage</label>
                <input id="packaging_type" name="packaging_type" class="nt-field" value="{{ old('packaging_type') }}" placeholder="carton, plastique…">
            </div>
        </div>
        <div>
            <label class="nt-label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="nt-field">{{ old('description') }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Créer</button>
            <a href="{{ route('products.index') }}" class="nt-btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
