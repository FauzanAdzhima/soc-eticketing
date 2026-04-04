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
    <title>{{ $title ?? 'CSIRT' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>

<body class="min-h-screen bg-background antialiased text-foreground">
    <div x-data="{
        sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        sidebarOpen: false
    }"
        x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebar-collapsed', value))"
        @toggle-sidebar.window="sidebarCollapsed = !sidebarCollapsed"
        @toggle-sidebar-mobile.window="sidebarOpen = !sidebarOpen"
        class="min-h-screen">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-scrim/40 lg:hidden"></div>

        <div class="fixed inset-y-0 left-0 z-40 w-72 transform transition-all duration-300 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'
            ]">
            <livewire:layout.sidebar />
        </div>

        <div class="flex min-h-screen flex-1 flex-col transition-[padding-left] duration-300"
            :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
            <livewire:layout.navbar />

            <main class="flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @livewireScripts
    @fluxScripts
</body>

</html>
