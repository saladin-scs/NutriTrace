@extends('layouts.front')

@section('title', 'Profil')

@section('content')
<div class="nt-reveal">
    <p class="nt-kicker">Compte</p>
    <h1 class="nt-page-title mt-1">Profil</h1>
    <p class="nt-page-sub">Informations personnelles, sécurité et accès ops.</p>
</div>

<div class="mt-6 grid gap-3 sm:grid-cols-3">
    <a href="{{ route('shipments.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Shipments</p>
        <p class="mt-1 text-sm text-nt-ink/55">Expéditions et livraisons</p>
    </a>
    <a href="{{ route('alert-center.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Alertes</p>
        <p class="mt-1 text-sm text-nt-ink/55">Anomalies à vérifier</p>
    </a>
    <a href="{{ route('cold-rooms.index') }}" class="rounded-2xl border border-[var(--nt-line)] bg-white/85 px-4 py-4 transition hover:border-emerald-700/30 hover:bg-white">
        <p class="font-display text-base font-semibold">Chambres froides</p>
        <p class="mt-1 text-sm text-nt-ink/55">Stock et température</p>
    </a>
</div>

<div class="mt-8 max-w-2xl space-y-5">
    <div class="nt-card nt-reveal nt-reveal-delay-1">
        @include('profile.partials.update-profile-information-form')
    </div>
    <div class="nt-card nt-reveal nt-reveal-delay-2">
        @include('profile.partials.update-password-form')
    </div>
    <div class="nt-card nt-reveal nt-reveal-delay-3 border-rose-200/60">
        @include('profile.partials.delete-user-form')
    </div>
</div>
@endsection
