<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CSIRT' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>

<body class="min-h-screen bg-white antialiased dark:bg-zinc-900">
    <x-public-navbar />

    <main class="mx-auto max-w-7xl px-4 py-6">
        {{ $slot }}
    </main>

    @livewireScripts
    @fluxScripts
</body>

</html>
