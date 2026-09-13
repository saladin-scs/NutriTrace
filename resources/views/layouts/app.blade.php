<!DOCTYPE html>
<html lang="fr">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'NutriTrace') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=fraunces:500,600,700|sora:400,500,600,700&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased text-nt-ink">
        <div class="min-h-screen bg-[var(--nt-surface)]">
            @include('layouts.navigation')

            @isset($header)
                <header class="border-b border-[var(--nt-line)] bg-white/70 backdrop-blur">
                    <div class="nt-container py-6">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main class="nt-container py-8">
                {{ $slot }}
            </main>
        </div>
    </body>
</html>
