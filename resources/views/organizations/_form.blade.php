@php
    /** @var \App\Models\Organization|null $organization */
    $organization = $organization ?? null;
    $location = $organization?->primaryLocation;
@endphp

<div class="grid gap-4 sm:grid-cols-2">
    <div class="sm:col-span-2">
        <label class="nt-label" for="name">Nom de l’organisation</label>
        <input id="name" name="name" type="text" required class="nt-field" value="{{ old('name', $organization?->name) }}">
        <x-input-error :messages="$errors->get('name')" class="mt-1" />
    </div>

    <div>
        <label class="nt-label" for="type">Type</label>
        <select id="type" name="type" required class="nt-field">
            @foreach ($types as $type)
                <option value="{{ $type->value }}" @selected(old('type', $organization?->type?->value) === $type->value)>
                    {{ $type->label() }}
                </option>
            @endforeach
        </select>
        <x-input-error :messages="$errors->get('type')" class="mt-1" />
    </div>

    <div>
        <label class="nt-label" for="email">Email</label>
        <input id="email" name="email" type="email" class="nt-field" value="{{ old('email', $organization?->email) }}">
        <x-input-error :messages="$errors->get('email')" class="mt-1" />
    </div>

    <div>
        <label class="nt-label" for="phone">Téléphone</label>
        <input id="phone" name="phone" type="text" class="nt-field" value="{{ old('phone', $organization?->phone) }}">
        <x-input-error :messages="$errors->get('phone')" class="mt-1" />
    </div>

    <div>
        <label class="nt-label" for="registration_number">N° d’enregistrement</label>
        <input id="registration_number" name="registration_number" type="text" class="nt-field" value="{{ old('registration_number', $organization?->registration_number) }}">
    </div>

    <div>
        <label class="nt-label" for="tax_id">Identifiant fiscal</label>
        <input id="tax_id" name="tax_id" type="text" class="nt-field" value="{{ old('tax_id', $organization?->tax_id) }}">
    </div>

    <div class="sm:col-span-2">
        <label class="nt-label" for="description">Description</label>
        <textarea id="description" name="description" rows="3" class="nt-field">{{ old('description', $organization?->description) }}</textarea>
    </div>

    <div class="sm:col-span-2">
        <label class="nt-label" for="address_line">Adresse</label>
        <input id="address_line" name="address_line" type="text" class="nt-field" value="{{ old('address_line', $location?->address_line) }}">
    </div>

    <div>
        <label class="nt-label" for="city">Ville</label>
        <input id="city" name="city" type="text" class="nt-field" value="{{ old('city', $location?->city) }}">
    </div>

    <div>
        <label class="nt-label" for="governorate">Gouvernorat</label>
        <input id="governorate" name="governorate" type="text" class="nt-field" value="{{ old('governorate', $location?->governorate ?? 'Tunis') }}">
    </div>
</div>
