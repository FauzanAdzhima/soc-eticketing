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
                payload: null,
                totalMs: 5000,
                remainingMs: 5000,
                tickMs: 50,
                timer: null,
                fadeOpacity() {
                    return Math.max(0, this.remainingMs / this.totalMs);
                },
                start() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    this.remainingMs = this.totalMs;
                    this.timer = setInterval(() => {
                        this.remainingMs -= this.tickMs;
                        if (this.remainingMs <= 0) {
                            this.remainingMs = 0;
                            this.hide();
                        }
                    }, this.tickMs);
                },
                show(detail) {
                    this.payload = detail;
                    this.remainingMs = this.totalMs;
                    this.$nextTick(() => {
                        const el = this.$refs.assignedToastPopover;
                        if (el && typeof el.showPopover === 'function') {
                            el.showPopover();
                        }
                        this.start();
                    });
                },
                hide() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    const el = this.$refs.assignedToastPopover;
                    if (el && typeof el.hidePopover === 'function') {
                        el.hidePopover();
                    }
                    this.payload = null;
                },
            }"
            @ticket-assigned.window="show($event.detail)"
        >
            <div
                popover="manual"
                x-ref="assignedToastPopover"
                x-show="payload"
                x-cloak
                class="app-toast-popover border-0 bg-transparent p-0 shadow-none"
            >
                <div
                    class="max-w-sm rounded-lg border border-sky-700 bg-sky-600 p-4 text-sky-50 shadow-lg dark:border-sky-400 dark:bg-sky-500 dark:text-white"
                    x-show="payload"
                    :style="{ opacity: fadeOpacity() }"
                    x-cloak
                    role="status"
                >
                    <p class="text-sm font-semibold text-sky-50 dark:text-white">Penugasan tiket baru</p>
                    <p class="mt-1 text-xs font-mono text-sky-100/90 dark:text-sky-100" x-text="payload?.ticket_number"></p>
                    <p class="mt-1 text-sm text-sky-50/95 dark:text-white/95" x-text="payload?.title"></p>
                    <div class="mt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="text-xs font-medium text-sky-100 hover:text-white dark:text-sky-100 dark:hover:text-white"
                            @click.stop.prevent="hide()"
                        >
                            Tutup
                        </button>
                        <a
                            :href="payload ? '{{ url('/tickets') }}?ticket=' + encodeURIComponent(payload.ticket_public_id) + '&scope=analyst' : '#'"
                            class="text-xs font-medium text-white underline decoration-white/60 underline-offset-2 hover:decoration-white"
                        >
                            Buka daftar
                        </a>
                    </div>
                </div>
            </div>
        </div>

        <div
            x-data="{
                payload: null,
                totalMs: 5000,
                remainingMs: 5000,
                tickMs: 50,
                timer: null,
                fadeOpacity() {
                    return Math.max(0, this.remainingMs / this.totalMs);
                },
                start() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    this.remainingMs = this.totalMs;
                    this.timer = setInterval(() => {
                        this.remainingMs -= this.tickMs;
                        if (this.remainingMs <= 0) {
                            this.remainingMs = 0;
                            this.hide();
                        }
                    }, this.tickMs);
                },
                show(detail) {
                    this.payload = detail;
                    this.remainingMs = this.totalMs;
                    this.$nextTick(() => {
                        const el = this.$refs.resolvedToastPopover;
                        if (el && typeof el.showPopover === 'function') {
                            el.showPopover();
                        }
                        this.start();
                    });
                },
                hide() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    const el = this.$refs.resolvedToastPopover;
                    if (el && typeof el.hidePopover === 'function') {
                        el.hidePopover();
                    }
                    this.payload = null;
                },
            }"
            @ticket-resolved.window="show($event.detail)"
        >
            <div
                popover="manual"
                x-ref="resolvedToastPopover"
                x-show="payload"
                x-cloak
                class="app-toast-popover border-0 bg-transparent p-0 shadow-none"
            >
                <div
                    class="max-w-sm rounded-lg border border-emerald-700 bg-emerald-600 p-4 text-emerald-50 shadow-lg dark:border-emerald-400 dark:bg-emerald-500 dark:text-white"
                    x-show="payload"
                    :style="{ opacity: fadeOpacity() }"
                    x-cloak
                    role="status"
                >
                    <p class="text-sm font-semibold text-emerald-50 dark:text-white">Tiket selesai ditangani</p>
                    <p class="mt-1 text-xs font-mono text-emerald-100/90 dark:text-emerald-100" x-text="payload?.ticket_number"></p>
                    <p class="mt-1 text-sm text-emerald-50/95 dark:text-white/95" x-text="payload?.title"></p>
                    <div class="mt-3 flex justify-end gap-2">
                        <a
                            :href="payload ? '{{ url('/tickets') }}?ticket=' + encodeURIComponent(payload.ticket_public_id) : '#'"
                            class="text-xs font-medium text-white underline decoration-white/60 underline-offset-2 hover:decoration-white"
                        >
                            Buka detail
                        </a>
                        <button
                            type="button"
                            class="text-xs font-medium text-emerald-100 hover:text-white dark:text-emerald-100 dark:hover:text-white"
                            @click.stop.prevent="hide()"
                        >
                            Tutup
                        </button>
                    </div>
                </div>
            </div>
        </div>
    @endauth

    @livewireScripts
    @fluxScripts
</body>

</html>
