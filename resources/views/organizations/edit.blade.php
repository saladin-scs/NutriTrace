@extends('layouts.front')

@section('title', 'Modifier organisation')

@section('content')
<div class="max-w-3xl">
    <h1 class="font-display text-3xl font-semibold">Modifier — {{ $organization->name }}</h1>

    <form method="POST" action="{{ route('organizations.update', $organization) }}" class="nt-card mt-6 space-y-6">
        @csrf
        @method('PUT')
        @include('organizations._form', ['organization' => $organization, 'types' => $types])
        <div class="flex gap-3">
            <button class="nt-btn" type="submit">Enregistrer</button>
            <a href="{{ route('organizations.show', $organization) }}" class="nt-btn-secondary">Retour</a>
        </div>
    </form>
</div>
@endsection
