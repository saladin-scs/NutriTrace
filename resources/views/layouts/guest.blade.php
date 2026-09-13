<!DOCTYPE html>
<html lang="fr">
    <head>
        @include('layouts.partials.head')
    </head>
    <body class="font-sans antialiased text-nt-ink">
        <div class="relative flex min-h-screen flex-col justify-center overflow-hidden px-4 py-10 sm:px-6">
            <div class="pointer-events-none absolute inset-0 -z-10">
                <div class="absolute inset-0 bg-[var(--nt-surface)]"></div>
                <div class="absolute -left-32 top-0 h-[28rem] w-[28rem] rounded-full bg-emerald-500/10 blur-3xl"></div>
                <div class="absolute -right-24 bottom-0 h-[24rem] w-[24rem] rounded-full bg-amber-400/10 blur-3xl"></div>
                <div class="nt-hero absolute inset-x-0 top-0 h-[42vh] opacity-90"></div>
                <div class="absolute inset-x-0 top-[28vh] h-40 bg-gradient-to-b from-transparent to-[var(--nt-surface)]"></div>
            </div>

            <div class="mx-auto w-full max-w-md">
                <a href="{{ route('home') }}" class="mb-8 flex flex-col items-center text-center">
                    <span class="nt-mark !h-12 !w-12 !rounded-2xl shadow-nt-lg">
                        <svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="!h-6 !w-6">
                            <path d="M12 3c-2.8 4.2-7 6.4-7 11a7 7 0 0 0 14 0c0-4.6-4.2-6.8-7-11Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/>
                            <path d="M12 10v8" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/>
                        </svg>
                    </span>
                    <span class="font-display mt-4 text-3xl font-semibold tracking-tight text-white drop-shadow">NutriTrace</span>
                    <span class="mt-1 text-sm text-emerald-50/80">De l’assiette à l’objectif</span>
                </a>

                <div class="nt-card nt-reveal !p-7 shadow-nt-lg">
                    {{ $slot }}
                </div>
            </div>
        </div>
    </body>
</html>
