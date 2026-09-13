@extends('layouts.admin')

@section('title', 'Nouvelle organisation')

@section('content')
<h1 class="font-display text-3xl">Nouvelle organisation</h1>
<form method="POST" action="{{ route('admin.organizations.store') }}" class="nt-card mt-6 max-w-3xl space-y-6">
    @csrf
    @include('organizations._form', ['types' => $types])
    <label class="flex items-center gap-2 text-sm">
        <input type="checkbox" name="verify_now" value="1" @checked(old('verify_now', true))>
        Vérifier immédiatement
    </label>
    <button class="nt-btn" type="submit">Créer</button>
</form>
@endsection
