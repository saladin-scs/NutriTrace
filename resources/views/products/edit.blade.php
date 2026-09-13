@extends('layouts.front')

@section('title', 'Modifier produit')

@section('content')
<div class="max-w-2xl">
    <h1 class="font-display text-3xl font-semibold">Modifier — {{ $product->name }}</h1>
    <form method="POST" action="{{ route('products.update', $product) }}" class="nt-card mt-6 space-y-4">
        @csrf
        @method('PUT')
        <div>
            <label class="nt-label" for="name">Nom</label>
            <input id="name" name="name" class="nt-field" value="{{ old('name', $product->name) }}" required>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="category_id">Catégorie</label>
                <select id="category_id" name="category_id" class="nt-field">
                    <option value="">—</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected(old('category_id', $product->category_id) == $category->id)>{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="nt-label" for="unit">Unité</label>
                <input id="unit" name="unit" class="nt-field" value="{{ old('unit', $product->unit) }}">
            </div>
        </div>
        <div class="grid gap-4 sm:grid-cols-2">
            <div>
                <label class="nt-label" for="sku">SKU</label>
                <input id="sku" name="sku" class="nt-field" value="{{ old('sku', $product->sku) }}">
            </div>
            <div>
                <label class="nt-label" for="packaging_type">Emballage</label>
                <input id="packaging_type" name="packaging_type" class="nt-field" value="{{ old('packaging_type', $product->packaging_type) }}">
            </div>
        </div>
        <div>
            <label class="nt-label" for="status">Statut</label>
            <select id="status" name="status" class="nt-field">
                @foreach (['draft','active','archived'] as $status)
                    <option value="{{ $status }}" @selected(old('status', $product->status) === $status)>{{ $status }}</option>
                @endforeach
            </select>
        </div>
        <div>
            <label class="nt-label" for="description">Description</label>
            <textarea id="description" name="description" rows="3" class="nt-field">{{ old('description', $product->description) }}</textarea>
        </div>
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Enregistrer</button>
            <a href="{{ route('products.show', $product) }}" class="nt-btn-secondary">Retour</a>
        </div>
    </form>
</div>
@endsection
