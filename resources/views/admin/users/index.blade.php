@extends('layouts.admin')

@section('title', 'Utilisateurs')

@section('content')
<h1 class="font-display text-3xl">Utilisateurs</h1>

<form method="GET" class="mt-4 flex flex-wrap gap-2">
    <input type="search" name="q" value="{{ request('q') }}" class="nt-field max-w-xs" placeholder="Nom ou email">
    <select name="role" class="nt-field max-w-[12rem]">
        <option value="">Rôle plateforme</option>
        @foreach ($roles as $role)
            <option value="{{ $role->value }}" @selected(request('role') === $role->value)>{{ $role->label() }}</option>
        @endforeach
    </select>
    <button class="nt-btn-secondary">Filtrer</button>
</form>

<div class="nt-card mt-6 overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead>
            <tr class="border-b text-left">
                <th class="py-2">Nom</th>
                <th>Email</th>
                <th>Rôle</th>
                <th>Orgs</th>
                <th></th>
            </tr>
        </thead>
        <tbody>
            @foreach ($users as $user)
                <tr class="border-b border-emerald-900/5">
                    <td class="py-3 font-medium">{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>{{ $user->role?->label() ?? '—' }}</td>
                    <td>{{ $user->organizations_count }}</td>
                    <td class="text-right">
                        <a href="{{ route('admin.users.show', $user) }}" class="text-emerald-700">Gérer</a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
<div class="mt-4">{{ $users->links() }}</div>
@endsection
