<nav x-data="{ open: false }" class="border-b border-[var(--nt-line)] bg-white/80 backdrop-blur-xl">
    <div class="nt-container flex h-16 items-center justify-between">
        <div class="flex items-center gap-8">
            <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
                <span class="nt-mark">
                    <svg viewBox="0 0 24 24" fill="none" aria-hidden="true">
                        <path d="M12 3c-2.8 4.2-7 6.4-7 11a7 7 0 0 0 14 0c0-4.6-4.2-6.8-7-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                        <path d="M12 10v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                    </svg>
                </span>
                <span class="font-display text-xl font-semibold">NutriTrace</span>
            </a>
            <div class="hidden sm:flex sm:gap-5">
                <a href="{{ route('dashboard') }}" class="nt-nav-link {{ request()->routeIs('dashboard') ? 'is-active' : '' }}">Dashboard</a>
                <a href="{{ route('profile.edit') }}" class="nt-nav-link {{ request()->routeIs('profile.*') ? 'is-active' : '' }}">Profil</a>
            </div>
        </div>

        <div class="hidden sm:flex sm:items-center">
            <x-dropdown align="right" width="48">
                <x-slot name="trigger">
                    <button class="inline-flex items-center gap-2 rounded-2xl border border-[var(--nt-line)] bg-white px-3 py-2 text-sm font-medium text-nt-ink transition hover:bg-nt-mist">
                        <div>{{ Auth::user()->name }}</div>
                        <svg class="h-4 w-4 fill-current text-nt-muted" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                        </svg>
                    </button>
                </x-slot>
                <x-slot name="content">
                    <x-dropdown-link :href="route('profile.edit')">Profil</x-dropdown-link>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault(); this.closest('form').submit();">
                            Déconnexion
                        </x-dropdown-link>
                    </form>
                </x-slot>
            </x-dropdown>
        </div>

        <div class="flex items-center sm:hidden">
            <button @click="open = ! open" class="inline-flex h-10 w-10 items-center justify-center rounded-xl border border-[var(--nt-line)] text-nt-muted">
                <svg class="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                    <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                    <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div :class="{'block': open, 'hidden': ! open}" class="hidden border-t border-[var(--nt-line)] sm:hidden">
        <div class="space-y-1 px-4 py-3">
            <a href="{{ route('dashboard') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Dashboard</a>
            <a href="{{ route('profile.edit') }}" class="block rounded-xl px-3 py-2.5 text-sm font-medium hover:bg-nt-mist">Profil</a>
        </div>
        <div class="border-t border-[var(--nt-line)] px-4 py-3">
            <div class="font-medium text-nt-ink">{{ Auth::user()->name }}</div>
            <div class="text-sm text-nt-muted">{{ Auth::user()->email }}</div>
            <form method="POST" action="{{ route('logout') }}" class="mt-2">
                @csrf
                <button class="text-sm font-medium text-nt-deep">Déconnexion</button>
            </form>
        </div>
    </div>
</nav>
