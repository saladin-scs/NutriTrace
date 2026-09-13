{{--
|--------------------------------------------------------------------------
| BACK OFFICE — template Blade administration
|--------------------------------------------------------------------------
| Utilisé pour : /admin/* (organisations, users, supervision)
| Front office : layouts/front.blade.php
--}}
<!DOCTYPE html>
<html lang="fr">
<head>
    @include('layouts.partials.head', ['titleSuffix' => ' — Admin'])
</head>
<body class="font-sans antialiased text-nt-ink" x-data="{ sidebarOpen: false }">
    <div class="min-h-screen lg:flex">
        <div x-cloak x-show="sidebarOpen" x-transition.opacity class="fixed inset-0 z-40 bg-nt-ink/40 lg:hidden" @click="sidebarOpen = false"></div>

        <aside
            class="fixed inset-y-0 left-0 z-50 flex w-72 -translate-x-full flex-col bg-[#07140d] p-5 text-emerald-50 transition-transform duration-300 ease-nt lg:static lg:translate-x-0"
            :class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
        >
            <a href="{{ route('admin.dashboard') }}" class="flex items-center gap-2.5">
                <span class="nt-mark !bg-white/10 !shadow-none">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3c-2.8 4.2-7 6.4-7 11a7 7 0 0 0 14 0c0-4.6-4.2-6.8-7-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 10v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <div>
                    <p class="font-display text-lg font-semibold leading-tight">NutriTrace</p>
                    <p class="text-[11px] uppercase tracking-[0.18em] text-emerald-200/60">Back office</p>
                </div>
            </a>

            <nav class="mt-10 flex flex-1 flex-col gap-1 text-sm">
                <p class="mb-2 px-3 text-[10px] uppercase tracking-[0.2em] text-emerald-200/40">Pilotage</p>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('admin.dashboard') ? 'bg-white/10 font-semibold' : '' }}" href="{{ route('admin.dashboard') }}">Tableau de bord</a>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('admin.organizations.*') ? 'bg-white/10 font-semibold' : '' }}" href="{{ route('admin.organizations.index') }}">Organisations</a>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10 {{ request()->routeIs('admin.users.*') ? 'bg-white/10 font-semibold' : '' }}" href="{{ route('admin.users.index') }}">Utilisateurs</a>

                <p class="mb-2 mt-6 px-3 text-[10px] uppercase tracking-[0.2em] text-emerald-200/40">Traçabilité</p>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10" href="{{ route('products.index') }}">Produits</a>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10" href="{{ route('batches.index') }}">Lots & QR</a>
                <a class="rounded-xl px-3 py-2.5 transition hover:bg-white/10" href="{{ route('cold-rooms.index') }}">Chambres & flux</a>

                <a class="mt-auto rounded-xl border border-white/10 px-3 py-2.5 text-emerald-100/80 transition hover:bg-white/10" href="{{ route('dashboard') }}">← Front office</a>
            </nav>
        </aside>

        <div class="flex min-h-screen flex-1 flex-col bg-[var(--nt-surface)]">
            <header class="sticky top-0 z-30 flex h-14 items-center justify-between gap-3 border-b border-[var(--nt-line)] bg-[var(--nt-surface)]/90 px-4 backdrop-blur-xl lg:px-8">
                <div class="flex items-center gap-3">
                    <button type="button" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--nt-line)] bg-white lg:hidden" @click="sidebarOpen = true" aria-label="Ouvrir le menu">
                        <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="1.8"><path stroke-linecap="round" d="M4 7h16M4 12h16M4 17h16"/></svg>
                    </button>
                    <div>
                        <p class="font-display text-lg font-semibold leading-tight">@yield('title', 'Admin')</p>
                        <p class="hidden text-xs text-nt-ink/45 sm:block">Bonjour, {{ auth()->user()->name }}</p>
                    </div>
                </div>
                <a href="{{ route('profile.edit') }}" class="nt-btn-secondary !py-2 text-xs">Profil</a>
            </header>

            <section class="flex-1 px-4 py-6 sm:px-6 lg:px-8">
                @if (session('success'))
                    <div class="nt-flash-ok mb-4 nt-reveal">{{ session('success') }}</div>
                @endif
                @if (session('error'))
                    <div class="nt-flash-err mb-4 nt-reveal">{{ session('error') }}</div>
                @endif
                @yield('content')
            </section>
        </div>
    </div>
</body>
</html>
