@extends('layouts.admin')

@section('title', 'Modifier organisation')

@section('content')
<h1 class="font-display text-3xl">Modifier — {{ $organization->name }}</h1>
<form method="POST" action="{{ route('admin.organizations.update', $organization) }}" class="nt-card mt-6 max-w-3xl space-y-6">
    @csrf
    @method('PUT')
    @include('organizations._form', ['organization' => $organization, 'types' => $types])
    <div class="flex gap-3">
        <button class="nt-btn" type="submit">Enregistrer</button>
        <a href="{{ route('admin.organizations.show', $organization) }}" class="nt-btn-secondary">Retour</a>
    </div>
</form>
@endsection
