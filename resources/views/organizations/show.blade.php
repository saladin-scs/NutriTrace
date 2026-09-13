@extends('layouts.front')

@section('title', $organization->name)

@section('content')
<div class="flex flex-wrap items-start justify-between gap-4">
    <div>
        <p class="text-xs uppercase tracking-[0.18em] text-nt-ink/45">Organisation</p>
        <h1 class="font-display text-3xl font-semibold">{{ $organization->name }}</h1>
        <p class="mt-1 text-sm text-nt-ink/60">
            {{ $organization->type->label() }} · {{ $organization->status->label() }}
        </p>
    </div>
    <div class="flex flex-wrap gap-2">
        @can('update', $organization)
            <a href="{{ route('organizations.edit', $organization) }}" class="nt-btn-secondary">Modifier</a>
        @endcan
        <a href="{{ route('organizations.index') }}" class="nt-btn-secondary">Liste</a>
    </div>
</div>

<div class="mt-8 grid gap-6 lg:grid-cols-2">
    <div class="nt-card space-y-2 text-sm">
        <h2 class="font-display text-lg font-semibold">Coordonnées</h2>
        <p><span class="text-nt-ink/50">Email</span> — {{ $organization->email ?: '—' }}</p>
        <p><span class="text-nt-ink/50">Téléphone</span> — {{ $organization->phone ?: '—' }}</p>
        <p><span class="text-nt-ink/50">Adresse</span> —
            {{ $organization->primaryLocation?->address_line ?: '—' }}
            @if ($organization->primaryLocation?->city)
                , {{ $organization->primaryLocation->city }}
            @endif
        </p>
        <p><span class="text-nt-ink/50">Gouvernorat</span> — {{ $organization->primaryLocation?->governorate ?: '—' }}</p>
        @if ($organization->description)
            <p class="pt-2 text-nt-ink/80">{{ $organization->description }}</p>
        @endif
    </div>

    <div class="nt-card">
        <div class="flex items-center justify-between gap-2">
            <h2 class="font-display text-lg font-semibold">Membres</h2>
            <span class="text-xs text-nt-ink/50">{{ $organization->users->count() }}</span>
        </div>
        <ul class="mt-4 divide-y divide-[var(--nt-line)] text-sm">
            @foreach ($organization->users as $member)
                <li class="flex items-center justify-between gap-3 py-3">
                    <div>
                        <p class="font-medium">{{ $member->name }}</p>
                        <p class="text-nt-ink/55">{{ $member->email }}
                            @if ($member->pivot->job_title) · {{ $member->pivot->job_title }} @endif
                            @if ($member->pivot->is_primary) · <span class="text-emerald-700">Responsable</span> @endif
                        </p>
                    </div>
                    @can('manageMembers', $organization)
                        @if ($member->id !== auth()->id() || $organization->users->count() > 1)
                            <form method="POST" action="{{ route('organizations.members.destroy', [$organization, $member]) }}" onsubmit="return confirm('Retirer ce membre ?')">
                                @csrf @method('DELETE')
                                <button class="text-red-600 text-xs">Retirer</button>
                            </form>
                        @endif
                    @endcan
                </li>
            @endforeach
        </ul>

        @can('manageMembers', $organization)
            <form method="POST" action="{{ route('organizations.members.store', $organization) }}" class="mt-6 space-y-3 border-t border-[var(--nt-line)] pt-4">
                @csrf
                <h3 class="text-sm font-semibold">Ajouter un membre</h3>
                <input type="email" name="email" class="nt-field" placeholder="email@exemple.tn" value="{{ old('email') }}" required>
                <x-input-error :messages="$errors->get('email')" />
                <select name="role_id" class="nt-field">
                    <option value="">Rôle org. (optionnel)</option>
                    @foreach ($roles as $role)
                        <option value="{{ $role->id }}" @selected(old('role_id') == $role->id)>{{ $role->name }}</option>
                    @endforeach
                </select>
                <input type="text" name="job_title" class="nt-field" placeholder="Fonction" value="{{ old('job_title') }}">
                <label class="flex items-center gap-2 text-sm">
                    <input type="checkbox" name="is_primary" value="1" @checked(old('is_primary'))>
                    Responsable principal
                </label>
                <button class="nt-btn" type="submit">Ajouter</button>
            </form>
        @endcan
    </div>
</div>
@endsection
