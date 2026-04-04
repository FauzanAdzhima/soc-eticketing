@props([
    'compact' => false,
])

<button type="button"
    x-data="{
        isDark: false,
        init() {
            this.isDark = document.documentElement.classList.contains('dark');
        },
        toggle() {
            // Always read the current DOM state so multiple toggles on the same page
            // don't get out of sync.
            const nextIsDark = !document.documentElement.classList.contains('dark');
            this.isDark = nextIsDark;

            try {
                localStorage.setItem('theme', nextIsDark ? 'dark' : 'light');
            } catch (e) {
                // localStorage might be blocked (privacy mode); still toggle UI classes.
            }

            document.documentElement.classList.toggle('dark', nextIsDark);
        },
    }"
    x-init="init()"
    @click="toggle()"
    aria-label="Toggle mode gelap/terang"
    :aria-pressed="isDark"
    {{ $attributes->class([
        'inline-flex items-center rounded-md border border-border bg-surface text-foreground transition hover:bg-muted focus:outline-none focus-visible:ring-2 focus-visible:ring-primary focus-visible:ring-offset-2 focus-visible:ring-offset-background',
        'h-10 w-10 justify-center' => $compact,
        'gap-2 px-3 py-2 text-sm font-medium' => !$compact,
    ]) }}>
    <span class="sr-only">Toggle mode gelap/terang</span>
    <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path
            d="M10 2.5a.75.75 0 0 1 .75.75v1.49a.75.75 0 1 1-1.5 0V3.25A.75.75 0 0 1 10 2.5ZM10 14.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V15a.75.75 0 0 1 .75-.75ZM4.74 4.74a.75.75 0 0 1 1.06 0l1.05 1.05a.75.75 0 1 1-1.06 1.06L4.74 5.8a.75.75 0 0 1 0-1.06Zm8.4 8.4a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 0 1 0-1.06ZM2.5 10a.75.75 0 0 1 .75-.75h1.49a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 10Zm12.75 0a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H16a.75.75 0 0 1-.75-.75ZM5.8 13.15a.75.75 0 0 1 1.05 0 .75.75 0 0 1 0 1.06L5.8 15.26a.75.75 0 1 1-1.06-1.06l1.06-1.05Zm8.4-8.4a.75.75 0 0 1 1.06 1.05L14.2 6.86a.75.75 0 1 1-1.06-1.06l1.06-1.05ZM10 6.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" />
    </svg>
    <svg class="h-5 w-5 shrink-0 dark:hidden" fill="currentColor" viewBox="0 0 35 35" xmlns="http://www.w3.org/2000/svg"
        aria-hidden="true">
        <path
            d="M18.44,34.68a18.22,18.22,0,0,1-2.94-.24,18.18,18.18,0,0,1-15-20.86A18.06,18.06,0,0,1,9.59.63,2.42,2.42,0,0,1,12.2.79a2.39,2.39,0,0,1,1,2.41L11.9,3.1l1.23.22A15.66,15.66,0,0,0,23.34,21h0a15.82,15.82,0,0,0,8.47.53A2.44,2.44,0,0,1,34.47,25,18.18,18.18,0,0,1,18.44,34.68ZM10.67,2.89a15.67,15.67,0,0,0-5,22.77A15.66,15.66,0,0,0,32.18,24a18.49,18.49,0,0,1-9.65-.64A18.18,18.18,0,0,1,10.67,2.89Z" />
    </svg>
    @unless($compact)
        <span class="hidden sm:inline dark:hidden">Mode Gelap</span>
        <span class="hidden sm:dark:inline">Mode Terang</span>
    @endunless
</button>
