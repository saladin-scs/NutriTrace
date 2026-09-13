@extends('layouts.front')

@section('title', $batch->code.' — Passeport')

@section('wide')
<div class="relative overflow-hidden border-b border-[var(--nt-line)] bg-[radial-gradient(ellipse_at_top,_#e8f5ee_0%,_#f5f9f6_50%,_#f7f4ef_100%)]">
    <div class="nt-container py-10 md:py-14">
        <div class="mx-auto max-w-3xl text-center">
            <p class="text-xs uppercase tracking-[0.2em] text-nt-ink/45">Digital Product Passport</p>
            <h1 class="mt-3 font-display text-4xl font-semibold tracking-tight md:text-5xl">{{ $batch->code }}</h1>
            <p class="mt-2 text-lg text-nt-ink/65">{{ $batch->product?->name }}</p>
            <img
                src="{{ $qrImageUrl }}"
                alt="QR {{ $batch->code }}"
                class="mx-auto mt-8 h-48 w-48 rounded-2xl border border-[var(--nt-line)] bg-white p-3 shadow-sm"
                width="192"
                height="192"
            >
            <p class="mt-3 break-all text-xs text-nt-ink/40">{{ $passportUrl }}</p>
        </div>
    </div>
</div>

<div class="nt-container max-w-3xl py-10">
    <section class="nt-card space-y-3 text-sm">
        <h2 class="font-display text-xl font-semibold">Informations produit</h2>
        <dl class="grid gap-3 sm:grid-cols-2">
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Catégorie</dt>
                <dd class="mt-0.5 font-medium">{{ $batch->product?->category?->name ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Origine</dt>
                <dd class="mt-0.5 font-medium">{{ $batch->product?->origin_country }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Producteur / détenteur</dt>
                <dd class="mt-0.5 font-medium">{{ $batch->organization?->name }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Production</dt>
                <dd class="mt-0.5 font-medium">{{ $batch->produced_at?->format('d/m/Y') ?: '—' }}</dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Lieu</dt>
                <dd class="mt-0.5 font-medium">
                    {{ $batch->productionLocation?->city ?: '—' }}{{ $batch->productionLocation?->governorate ? ', '.$batch->productionLocation->governorate : '' }}
                </dd>
            </div>
            <div>
                <dt class="text-xs uppercase tracking-wide text-nt-ink/40">Quantité</dt>
                <dd class="mt-0.5 font-medium">{{ $batch->quantity }} {{ $batch->unit }}</dd>
            </div>
        </dl>
    </section>

    <section class="nt-card mt-6">
        <h2 class="font-display text-xl font-semibold">Traçabilité</h2>
        <ol class="mt-6 space-y-0">
            @foreach ($nodes as $index => $node)
                <li class="relative flex gap-4 pb-7 last:pb-0">
                    @if (! $loop->last)
                        <span class="absolute left-[0.7rem] top-7 h-[calc(100%-0.75rem)] w-px bg-emerald-800/25"></span>
                    @endif
                    <span class="relative z-10 mt-1 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-900 text-[10px] font-bold text-white">{{ $index + 1 }}</span>
                    <div>
                        <p class="font-medium">{{ $node->label }}</p>
                        <p class="text-sm text-nt-ink/55">
                            {{ $node->organizationName }}
                            @if ($node->locationLabel) · {{ $node->locationLabel }} @endif
                        </p>
                        @if ($node->occurredAt)
                            <p class="text-xs text-nt-ink/40">{{ \Illuminate\Support\Carbon::parse($node->occurredAt)->format('d/m/Y H:i') }}</p>
                        @endif
                    </div>
                </li>
            @endforeach
        </ol>
    </section>

    @if ($lineage->count() > 1)
        <section class="nt-card mt-6 text-sm">
            <h2 class="font-display text-xl font-semibold">Lignée des lots</h2>
            <p class="mt-3 font-medium text-nt-ink/80">{{ $lineage->pluck('code')->implode(' → ') }}</p>
        </section>
    @endif

    <p class="mt-10 text-center text-xs text-nt-ink/40">
        Passeport généré par NutriTrace — Grand Tunis
    </p>
</div>
@endsection
