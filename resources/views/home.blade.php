@extends('layouts.front')

@section('title', 'NutriTrace — Traçabilité alimentaire')

@section('wide')
<section class="nt-hero relative min-h-[calc(100vh-4rem)]">
    <div class="nt-hero-glow -left-24 -top-24"></div>
    <div class="nt-hero-glow bottom-0 right-0 opacity-60" style="animation-delay: -6s;"></div>

    <div class="nt-container relative flex min-h-[calc(100vh-4rem)] flex-col justify-center py-16 md:py-20">
        <div class="max-w-2xl">
            <p class="nt-reveal text-[11px] font-semibold uppercase tracking-[0.28em] text-emerald-100/70">
                Food Traceability · Grand Tunis
            </p>
            <h1 class="font-display nt-reveal nt-reveal-delay-1 mt-5 text-5xl font-semibold leading-[1.05] tracking-tight text-white md:text-7xl">
                NutriTrace
            </h1>
            <p class="nt-reveal nt-reveal-delay-2 mt-6 max-w-lg text-lg leading-relaxed text-emerald-50/85 md:text-xl">
                Tracez les produits et lots, suivez les flux de distribution et les chambres froides, mesurez l’impact et agissez sur les déchets.
            </p>
            <div class="nt-reveal nt-reveal-delay-3 mt-10 flex flex-wrap gap-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="nt-btn-light">Mon espace</a>
                    <a href="{{ route('batches.index') }}" class="nt-btn-ghost border border-white/25">Lots & QR</a>
                @else
                    <a href="{{ route('register') }}" class="nt-btn-light">Commencer</a>
                    <a href="{{ route('login') }}" class="nt-btn-ghost border border-white/25">Se connecter</a>
                @endauth
            </div>
        </div>
    </div>

    <div class="pointer-events-none absolute inset-x-0 bottom-0 h-32 bg-gradient-to-t from-[var(--nt-surface)] to-transparent"></div>
</section>

<section class="nt-container relative z-10 -mt-8 pb-20 md:-mt-12">
    <div class="grid gap-5 md:grid-cols-3">
        @foreach ([
            ['01', 'Traçabilité', 'Produits, lots et passeport numérique QR de la ferme à l’assiette.'],
            ['02', 'Flux & chambres froides', 'Historisez entrées, sorties, acteurs, conditions et durées.'],
            ['03', 'Impact & déchets', 'Mesurez, auditisez et préparez la valorisation circulaire.'],
        ] as $i => [$num, $title, $text])
            <article class="nt-card nt-card-interactive nt-reveal" style="animation-delay: {{ 0.1 + ($i * 0.08) }}s">
                <p class="font-display text-sm text-nt-leaf">{{ $num }}</p>
                <h2 class="font-display mt-3 text-2xl text-nt-ink">{{ $title }}</h2>
                <p class="mt-2 text-sm leading-relaxed text-nt-muted">{{ $text }}</p>
            </article>
        @endforeach
    </div>
</section>
@endsection
