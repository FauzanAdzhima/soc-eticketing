<!DOCTYPE html>
<html lang="id">

<head>
    <script>
        (function() {
            try {
                var stored = localStorage.getItem('theme');
                var prefersDark = window.matchMedia && window.matchMedia('(prefers-color-scheme: dark)').matches;
                var useDark = stored === 'dark' || ((stored === null || stored === 'system') && prefersDark);

                document.documentElement.classList.toggle('dark', !!useDark);
            } catch (e) {}
        })();
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'CSIRT - Provinsi Kepulauan Riau' }}</title>
    @include('partials.favicon')
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-background antialiased text-foreground">
    <x-public-navbar />

    <main class="mx-auto max-w-7xl px-4 py-6">
        {{ $slot }}
    </main>
    <x-public-footer />

    @livewireScripts
    @fluxScripts
</body>

</html>
