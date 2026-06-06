<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ config('app.name') }}</title>
    @fluxAppearance
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen bg-zinc-50 dark:bg-zinc-950 antialiased">
    <div class="min-h-screen flex flex-col">
        <header class="sticky top-0 z-10 border-b border-zinc-200 dark:border-zinc-800 bg-white/80 dark:bg-zinc-900/80 backdrop-blur-sm">
            <div class="max-w-2xl mx-auto w-full px-4 sm:px-6 h-14 flex items-center justify-between">
                <span class="font-semibold text-sm text-zinc-900 dark:text-white">
                    {{ config('app.name') }}
                </span>
                @auth
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ auth()->user()->name }}
                    </span>
                @endauth
            </div>
        </header>

        <main class="flex-1 max-w-2xl mx-auto w-full px-4 sm:px-6 py-8">
            {{ $slot }}
        </main>
    </div>

    @livewireScripts
    @fluxScripts
</body>
</html>
