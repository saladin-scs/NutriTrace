<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-nt-ink">Créer un compte</h1>
        <p class="mt-1 text-sm text-nt-muted">Rejoignez la plateforme de traçabilité alimentaire.</p>
    </div>

    <form method="POST" action="{{ route('register') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="name" value="Nom" class="nt-label" />
            <x-text-input id="name" class="nt-field block w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="email" value="E-mail" class="nt-label" />
            <x-text-input id="email" class="nt-field block w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" class="nt-label" />
            <x-text-input id="password" class="nt-field block w-full"
                            type="password"
                            name="password"
                            required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password_confirmation" value="Confirmer le mot de passe" class="nt-label" />
            <x-text-input id="password_confirmation" class="nt-field block w-full"
                            type="password"
                            name="password_confirmation" required autocomplete="new-password" />
            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <button class="nt-btn w-full !py-3">S’inscrire</button>

        <p class="text-center text-sm text-nt-muted">
            Déjà inscrit ?
            <a href="{{ route('login') }}" class="nt-link">Se connecter</a>
        </p>
    </form>
</x-guest-layout>
