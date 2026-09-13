<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-nt-ink">Connexion</h1>
        <p class="mt-1 text-sm text-nt-muted">Accédez à votre espace NutriTrace.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('login') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" class="nt-label" />
            <x-text-input id="email" class="nt-field block w-full" type="email" name="email" :value="old('email')" required autofocus autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <div>
            <x-input-label for="password" value="Mot de passe" class="nt-label" />
            <x-text-input id="password" class="nt-field block w-full"
                            type="password"
                            name="password"
                            required autocomplete="current-password" />
            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <div class="flex items-center justify-between gap-3">
            <label for="remember_me" class="inline-flex items-center">
                <input id="remember_me" type="checkbox" class="rounded border-[var(--nt-line)] text-nt-leaf shadow-sm focus:ring-nt-leaf" name="remember">
                <span class="ms-2 text-sm text-nt-muted">Se souvenir de moi</span>
            </label>

            @if (Route::has('password.request'))
                <a class="text-sm font-medium text-nt-deep hover:text-nt-leaf" href="{{ route('password.request') }}">
                    Mot de passe oublié ?
                </a>
            @endif
        </div>

        <button class="nt-btn w-full !py-3">Se connecter</button>

        <p class="text-center text-sm text-nt-muted">
            Pas encore de compte ?
            <a href="{{ route('register') }}" class="nt-link">Créer un compte</a>
        </p>
    </form>
</x-guest-layout>
