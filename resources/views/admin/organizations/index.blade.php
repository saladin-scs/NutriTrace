@extends('layouts.admin')

@section('title', 'Organisations')

@section('content')
<div class="flex flex-wrap items-center justify-between gap-4">
    <h1 class="font-display text-3xl">Organisations</h1>
    <a href="{{ route('admin.organizations.create') }}" class="nt-btn">Nouvelle</a>
</div>

<form method="GET" class="mt-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="nt-field max-w-xs" placeholder="Recherche">
    <select name="status" class="nt-field max-w-[10rem]">
        <option value="">Statut</option>
        @foreach ($statuses as $status)
            <option value="{{ $status->value }}" @selected(request('status') === $status->value)>{{ $status->label() }}</option>
        @endforeach
    </select>
    <select name="type" class="nt-field max-w-[12rem]">
        <option value="">Type</option>
        @foreach ($types as $type)
            <option value="{{ $type->value }}" @selected(request('type') === $type->value)>{{ $type->label() }}</option>
        @endforeach
    </select>
    <button class="nt-btn-secondary">Filtrer</button>
</form>

<div class="nt-card mt-6 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b text-left">
                <th class="py-2">Nom</th>
                <th>Type</th>
                <th>Statut</th>
                <th>Membres</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($organizations as $organization)
                <tr class="border-b border-emerald-900/5">
                    <td class="py-3 font-medium">{{ $organization->name }}</td>
                    <td>{{ $organization->type->label() }}</td>
                    <td>{{ $organization->status->label() }}</td>
                    <td>{{ $organization->users->count() }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.organizations.show', $organization) }}" class="text-emerald-700">Voir</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $organizations->links() }}</div>
@endsection
