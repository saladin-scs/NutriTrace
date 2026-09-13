<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-nt-ink">Nouveau mot de passe</h1>
        <p class="mt-1 text-sm text-nt-muted">Choisissez un mot de passe sécurisé pour votre compte.</p>
    </div>

    <form method="POST" action="{{ route('password.store') }}" class="space-y-4">
        @csrf
        <input type="hidden" name="token" value="{{ $request->route('token') }}">

        <div>
            <x-input-label for="email" value="E-mail" class="nt-label" />
            <x-text-input id="email" class="nt-field block w-full" type="email" name="email" :value="old('email', $request->email)" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" class="nt-label" />
            <x-text-input id="password" class="nt-field block w-full" type="password" name="password" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmer" class="nt-label" />
            <x-text-input id="password_confirmation" class="nt-field block w-full"
                                type="password"
                                name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button class="nt-btn w-full !py-3">Réinitialiser</button>
    </form>
</x-guest-layout>
