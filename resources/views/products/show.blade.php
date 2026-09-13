@extends('layouts.front')

@section('title', $product->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="nt-kicker">Produit</p>
        <h1 class="nt-page-title mt-1">{{ $product->name }}</h1>
        <p class="nt-page-sub">{{ $product->organization?->name }} · {{ $product->category?->name ?? 'Sans catégorie' }}</p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('update', $product)
            <a href="{{ route('products.edit', $product) }}" class="nt-btn-secondary">Modifier</a>
            <a href="{{ route('products.batches.create', $product) }}" class="nt-btn">Nouveau lot</a>
        @endcan
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-3">
    <div class="nt-card space-y-2 text-sm lg:col-span-1">
        <h2 class="font-display text-lg font-semibold">Fiche</h2>
        <p>SKU — {{ $product->sku ?: '—' }}</p>
        <p>Unité — {{ $product->unit }}</p>
        <p>Emballage — {{ $product->packaging_type ?: '—' }}</p>
        <p>Origine — {{ $product->origin_country }}</p>
        @if ($product->description)
            <p class="pt-2 text-nt-ink/80">{{ $product->description }}</p>
        @endif
    </div>
    <div class="nt-card lg:col-span-2">
        <h2 class="font-display text-lg font-semibold">Lots</h2>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @forelse ($product->batches as $batch)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <a href="{{ route('batches.show', $batch) }}" class="font-medium text-emerald-800">{{ $batch->code }}</a>
                        <p class="text-nt-ink/55">{{ $batch->quantity }} {{ $batch->unit }} · {{ $batch->status->value }}</p>
                    </div>
                    <a href="{{ route('trace.show', $batch->code) }}" class="text-xs text-nt-ink/60 underline" target="_blank">Passeport</a>
                </li>
            @empty
                <li class="py-3 text-nt-ink/55">Aucun lot.</li>
            @endforelse
        </ul>
    </div>
</div>
@endsection
