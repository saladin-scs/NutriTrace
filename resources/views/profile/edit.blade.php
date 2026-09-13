@extends('layouts.front')

@section('title', 'Profil')

@section('content')
<div class="nt-reveal">
    <p class="nt-kicker">Compte</p>
    <h1 class="nt-page-title mt-1">Profil</h1>
    <p class="nt-page-sub">Informations personnelles et sécurité.</p>
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
