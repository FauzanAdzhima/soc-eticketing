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
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @auth
        <meta name="user-id" content="{{ auth()->id() }}">
    @endauth
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
                    class="max-w-sm rounded-lg border border-border-strong bg-info p-4 text-info-foreground shadow-lg"
                    x-show="payload"
                    :style="{ opacity: fadeOpacity() }"
                    x-cloak
                    role="status"
                >
                    <p class="text-sm font-semibold text-info-foreground">Penugasan tiket baru</p>
                    <p class="mt-1 text-xs font-mono text-info-foreground/90" x-text="payload?.ticket_number"></p>
                    <p class="mt-1 text-sm text-info-foreground/95" x-text="payload?.title"></p>
                    <div class="mt-3 flex justify-end gap-2">
                        <button
                            type="button"
                            class="text-xs font-medium text-info-foreground/90 hover:text-info-foreground"
                            @click.stop.prevent="hide()"
                        >
                            Tutup
                        </button>
                        <a
                            :href="payload ? '{{ url('/tickets') }}?ticket=' + encodeURIComponent(payload.ticket_public_id) + '&scope=analyst' : '#'"
                            class="text-xs font-medium text-info-foreground underline decoration-info-foreground/60 underline-offset-2 hover:decoration-info-foreground"
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
                    class="max-w-sm rounded-lg border border-border-strong bg-success p-4 text-success-foreground shadow-lg"
                    x-show="payload"
                    :style="{ opacity: fadeOpacity() }"
                    x-cloak
                    role="status"
                >
                    <p class="text-sm font-semibold text-success-foreground">Tiket selesai ditangani</p>
                    <p class="mt-1 text-xs font-mono text-success-foreground/90" x-text="payload?.ticket_number"></p>
                    <p class="mt-1 text-sm text-success-foreground/95" x-text="payload?.title"></p>
                    <div class="mt-3 flex justify-end gap-2">
                        <a
                            :href="payload ? '{{ url('/tickets') }}?ticket=' + encodeURIComponent(payload.ticket_public_id) : '#'"
                            class="text-xs font-medium text-success-foreground underline decoration-success-foreground/60 underline-offset-2 hover:decoration-success-foreground"
                        >
                            Buka detail
                        </a>
                        <button
                            type="button"
                            class="text-xs font-medium text-success-foreground/90 hover:text-success-foreground"
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
