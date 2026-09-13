<x-guest-layout>
    <div class="mb-6">
        <h1 class="font-display text-2xl font-semibold text-nt-ink">Mot de passe oublié</h1>
        <p class="mt-1 text-sm text-nt-muted">Indiquez votre e-mail : nous vous enverrons un lien de réinitialisation.</p>
    </div>

    <x-auth-session-status class="mb-4" :status="session('status')" />

    <form method="POST" action="{{ route('password.email') }}" class="space-y-4">
        @csrf

        <div>
            <x-input-label for="email" value="E-mail" class="nt-label" />
            <x-text-input id="email" class="nt-field block w-full" type="email" name="email" :value="old('email')" required autofocus />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <button class="nt-btn w-full !py-3">Envoyer le lien</button>

        <p class="text-center text-sm text-nt-muted">
            <a href="{{ route('login') }}" class="nt-link">Retour à la connexion</a>
        </p>
    </form>
</x-guest-layout>
