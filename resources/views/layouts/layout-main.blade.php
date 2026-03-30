<!DOCTYPE html>
<html lang="id">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
    <title>{{ $title ?? 'CSIRT' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @fluxAppearance
</head>

<body class="min-h-screen bg-zinc-50 antialiased dark:bg-zinc-950">
    <div x-data="{
        sidebarCollapsed: localStorage.getItem('sidebar-collapsed') === 'true',
        sidebarOpen: false
    }"
        x-init="$watch('sidebarCollapsed', value => localStorage.setItem('sidebar-collapsed', value))"
        @toggle-sidebar.window="sidebarCollapsed = !sidebarCollapsed"
        @toggle-sidebar-mobile.window="sidebarOpen = !sidebarOpen"
        class="min-h-screen">
        <div x-show="sidebarOpen" x-cloak @click="sidebarOpen = false"
            class="fixed inset-0 z-30 bg-zinc-950/40 lg:hidden"></div>

        <div class="fixed inset-y-0 left-0 z-40 w-72 transform transition-all duration-300 lg:translate-x-0"
            :class="[
                sidebarOpen ? 'translate-x-0' : '-translate-x-full',
                sidebarCollapsed ? 'lg:w-20' : 'lg:w-72'
            ]">
            <livewire:layout.sidebar />
        </div>

        <div class="flex min-h-screen min-w-0 flex-1 flex-col transition-[padding-left] duration-300"
            :class="sidebarCollapsed ? 'lg:pl-20' : 'lg:pl-72'">
            <livewire:layout.navbar />

            <main class="min-w-0 flex-1 p-4 sm:p-6">
                {{ $slot }}
            </main>
        </div>
    </div>

    @auth
        <div
            x-data="{
                open: false,
                payload: null,
                reset() {
                    this.open = false;
                    this.payload = null;
                },
            }"
            @ticket-assigned.window="open = true; payload = $event.detail"
            x-show="open"
            x-cloak
            class="fixed bottom-4 right-4 z-50 max-w-sm"
            role="status"
        >
            <div
                class="rounded-lg border border-sky-200 bg-white p-4 shadow-lg dark:border-sky-800/60 dark:bg-zinc-900"
                x-show="open && payload"
            >
                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Penugasan tiket baru</p>
                <p class="mt-1 text-xs font-mono text-zinc-500 dark:text-zinc-400" x-text="payload?.ticket_number"></p>
                <p class="mt-1 text-sm text-zinc-700 dark:text-zinc-300" x-text="payload?.title"></p>
                <div class="mt-3 flex justify-end gap-2">
                    <button
                        type="button"
                        class="text-xs font-medium text-zinc-500 hover:text-zinc-800 dark:text-zinc-400 dark:hover:text-zinc-200"
                        @click="reset()"
                    >
                        Tutup
                    </button>
                    <a
                        :href="payload ? '{{ url('/tickets') }}?ticket=' + encodeURIComponent(payload.ticket_public_id) + '&scope=analyst' : '#'"
                        class="text-xs font-medium text-sky-600 hover:text-sky-800 dark:text-sky-400 dark:hover:text-sky-300"
                    >
                        Buka daftar
                    </a>
                </div>
            </div>
        </div>
    @endauth

    @livewireScripts
    @fluxScripts
</body>

</html>
