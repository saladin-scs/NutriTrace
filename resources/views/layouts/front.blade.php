{{--
|--------------------------------------------------------------------------
| FRONT OFFICE — template Blade public / acteurs
|--------------------------------------------------------------------------
| Utilisé pour : dashboard, organisations, produits, lots, chambres froides, passeport…
| Back office  : layouts/admin.blade.php
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('layouts.partials.head')
</head>
<body class="flex min-h-screen flex-col font-sans antialiased text-nt-ink" x-data="{ mobileOpen: false }">
    <header class="sticky top-0 z-40 border-b border-[var(--nt-line)] bg-[var(--nt-surface)]/85 backdrop-blur-xl">
        <div class="nt-container flex h-16 items-center justify-between gap-4">
            <a href="{{ route('home') }}" class="group flex items-center gap-2.5">
                <span class="nt-mark transition group-hover:scale-105">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3c-2.8 4.2-7 6.4-7 11a7 7 0 0 0 14 0c0-4.6-4.2-6.8-7-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 10v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="font-display text-xl font-semibold tracking-tight text-nt-ink">NutriTrace</span>
            </a>

            <nav class="hidden items-center gap-5 lg:flex">
                @auth
                    <a href="{{ route('dashboard') }}" class="nt-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
                    <a href="{{ route('organizations.index') }}" class="nt-nav-link {{ request()->routeIs('organizations.*') ? 'is-active' : '' }}">Organisations</a>
                    <a href="{{ route('products.index') }}" class="nt-nav-link {{ request()->routeIs('products.*') ? 'is-active' : '' }}">Produits</a>
                    <a href="{{ route('batches.index') }}" class="nt-nav-link {{ request()->routeIs('batches.*') ? 'is-active' : '' }}">Lots</a>
                    <a href="{{ route('cold-rooms.index') }}" class="nt-nav-link {{ request()->routeIs('cold-rooms.*') ? 'is-active' : '' }}">Chambres froides</a>
                    <a href="{{ route('profile.edit') }}" class="nt-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">Profil</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="nt-nav-link text-nt-honey">Back office</a>
                    @endif
                @else
                    <a href="{{ route('login') }}" class="nt-nav-link">Connexion</a>
                    <a href="{{ route('register') }}" class="nt-btn !py-2">Inscription</a>
                @endauth
            </nav>

            <div class="flex items-center gap-2">
                @auth
                    <x-realtime-notifications />
                    <form method="POST" action="{{ route('logout') }}" class="hidden sm:block">
                        @csrf
                        <button class="nt-btn-secondary !px-3.5 !py-2 text-xs">Déconnexion</button>
                    </form>
                @endauth
                <button type="button"
                        class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--nt-line)] bg-white/70 lg:hidden"
                        @click="mobileOpen = !mobileOpen"
                        :aria-expanded="mobileOpen.toString()"
                        aria-label="Menu">
                    <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8">
                        <path x-show="!mobileOpen" stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/>
                        <path x-cloak x-show="mobileOpen" stroke-linecap="round" d="M6 6l12 12M18 6L6 18"/>
                    </svg>
                </button>
            </div>
        </div>

        <div x-cloak x-show="mobileOpen" x-transition class="border-t border-[var(--nt-line)] bg-[var(--nt-surface)] lg:hidden">
            <nav class="nt-container flex flex-col gap-1 py-3">
                @auth
                    <a href="{{ route('dashboard') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Dashboard</a>
                    <a href="{{ route('organizations.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Organisations</a>
                    <a href="{{ route('products.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Produits</a>
                    <a href="{{ route('batches.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Lots</a>
                    <a href="{{ route('cold-rooms.index') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Chambres froides</a>
                    <a href="{{ route('profile.edit') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Profil</a>
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium text-nt-honey hover:bg-nt-mist">Back office</a>
                    @endif
                    <form method="POST" action="{{ route('logout') }}" class="mt-2 border-t border-[var(--nt-line)] pt-2 sm:hidden">
                        @csrf
                        <button class="w-full rounded-xl px-3 py-2.5 text-left text-sm font-medium hover:bg-nt-mist">Déconnexion</button>
                    </form>
                @else
                    <a href="{{ route('login') }}" class="rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Connexion</a>
                    <a href="{{ route('register') }}" class="nt-btn mt-1">Inscription</a>
                @endauth
            </nav>
        </div>
    </header>

    @if (session('success') || session('error'))
        <div class="nt-container mt-4 space-y-2">
            @if (session('success'))
                <div class="nt-flash-ok nt-reveal">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="nt-flash-err nt-reveal">{{ session('error') }}</div>
            @endif
        </div>
    @endif

    @hasSection('wide')
        <div class="flex-1">
            @yield('wide')
        </div>
    @else
        <main class="nt-container flex-1 py-8 md:py-10">
            @yield('content')
        </main>
    @endif

    <footer class="mt-auto border-t border-[var(--nt-line)] bg-white/40">
        <div class="nt-container flex flex-col gap-2 py-8 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="font-display text-lg text-nt-ink">NutriTrace</p>
                <p class="text-xs uppercase tracking-[0.14em] text-nt-ink/40">Front office</p>
            </div>
            <p class="text-sm text-nt-muted">Traçabilité alimentaire · Impact · Déchets — Grand Tunis</p>
        </div>
    </footer>
</body>
</html>
