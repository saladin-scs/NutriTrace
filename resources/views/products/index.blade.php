@extends('layouts.front')

@section('title', 'Produits')

@section('content')
<div class="flex flex-wrap items-end justify-between gap-4">
    <div>
        <p class="nt-kicker">Traçabilité</p>
        <h1 class="nt-page-title mt-1">Produits</h1>
        <p class="nt-page-sub">Fiches produits de vos organisations.</p>
    </div>
    @can('create', App\Models\Product::class)
        <a href="{{ route('products.create') }}" class="nt-btn">Nouveau produit</a>
    @endcan
</div>

<form method="GET" class="mt-6 flex gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="nt-field max-w-sm" placeholder="Nom ou SKU">
    <button class="nt-btn-secondary">Filtrer</button>
</form>

<div class="mt-6 grid gap-3">
    @forelse ($products as $product)
        <a href="{{ route('products.show', $product) }}" class="nt-card block transition hover:-translate-y-0.5">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div>
                    <h2 class="font-display text-xl font-semibold">{{ $product->name }}</h2>
                    <p class="mt-1 text-sm text-nt-ink/60">
                        {{ $product->organization?->name }}
                        @if ($product->category) · {{ $product->category->name }} @endif
                        · {{ $product->batches_count }} lot(s)
                    </p>
                </div>
                <span class="rounded-full bg-nt-mist px-3 py-1 text-xs font-medium">{{ $product->status }}</span>
            </div>
        </a>
    @empty
        <div class="nt-card text-sm text-nt-ink/70">Aucun produit. Créez d’abord une organisation, puis un produit.</div>
    @endforelse
</div>
<div class="mt-6">{{ $products->links() }}</div>
@endsection
