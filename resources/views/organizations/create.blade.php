@extends('layouts.front')

@section('title', 'Nouvelle organisation')

@section('content')
<div class="max-w-3xl">
    <h1 class="font-display text-3xl font-semibold">Nouvelle organisation</h1>
    <p class="mt-1 text-sm text-nt-ink/60">Déclaration initiale — statut « en attente » jusqu’à validation admin.</p>

    <form method="POST" action="{{ route('organizations.store') }}" class="nt-card mt-6 space-y-6">
        @csrf
        @include('organizations._form', ['types' => $types])
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Créer</button>
            <a href="{{ route('organizations.index') }}" class="nt-btn-secondary">Annuler</a>
        </div>
    </form>
</div>
@endsection
