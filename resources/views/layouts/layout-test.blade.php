<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <title>{{ $title ?? 'CSIRT' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>

<body class="min-h-screen bg-white dark:bg-zinc-800 antialiased">

    @unless (request()->routeIs('login'))
        <x-header />
    @endunless

    <main class="max-w-7xl mx-auto" @class(['py-6', 'px-4' => !request()->routeIs('login')])>
        {{ $slot }}
    </main>

    @unless (request()->routeIs('login'))
        <x-footer />
    @endunless

    @livewireScripts
    @fluxScripts
</body>

</html>
