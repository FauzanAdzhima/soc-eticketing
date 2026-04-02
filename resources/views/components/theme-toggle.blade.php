@props([
    'compact' => false,
])

<button type="button"
    x-data
    @click="
        if (window.Flux && typeof window.Flux.dark !== 'undefined') {
            window.Flux.dark = !window.Flux.dark;
        } else {
            document.documentElement.classList.toggle('dark');
        }
    "
    aria-label="Toggle mode gelap/terang"
    {{ $attributes->class([
        'inline-flex items-center rounded-md border transition focus:outline-none focus-visible:ring-2 focus-visible:ring-offset-2 focus-visible:ring-blue-500 dark:focus-visible:ring-offset-zinc-900',
        'h-10 w-10 justify-center border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700' => $compact,
        'gap-2 px-3 py-2 text-sm font-medium border-zinc-300 bg-white text-zinc-700 hover:bg-zinc-100 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 dark:hover:bg-zinc-700' => !$compact,
    ]) }}>
    <span class="sr-only">Toggle mode gelap/terang</span>
    <svg class="hidden h-5 w-5 dark:block" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
        <path
            d="M10 2.5a.75.75 0 0 1 .75.75v1.49a.75.75 0 1 1-1.5 0V3.25A.75.75 0 0 1 10 2.5ZM10 14.25a.75.75 0 0 1 .75.75v1.5a.75.75 0 0 1-1.5 0V15a.75.75 0 0 1 .75-.75ZM4.74 4.74a.75.75 0 0 1 1.06 0l1.05 1.05a.75.75 0 1 1-1.06 1.06L4.74 5.8a.75.75 0 0 1 0-1.06Zm8.4 8.4a.75.75 0 0 1 1.06 0l1.06 1.06a.75.75 0 1 1-1.06 1.06l-1.06-1.06a.75.75 0 0 1 0-1.06ZM2.5 10a.75.75 0 0 1 .75-.75h1.49a.75.75 0 0 1 0 1.5H3.25A.75.75 0 0 1 2.5 10Zm12.75 0a.75.75 0 0 1 .75-.75h1.5a.75.75 0 0 1 0 1.5H16a.75.75 0 0 1-.75-.75ZM5.8 13.15a.75.75 0 0 1 1.05 0 .75.75 0 0 1 0 1.06L5.8 15.26a.75.75 0 1 1-1.06-1.06l1.06-1.05Zm8.4-8.4a.75.75 0 0 1 1.06 1.05L14.2 6.86a.75.75 0 1 1-1.06-1.06l1.06-1.05ZM10 6.25a3.75 3.75 0 1 0 0 7.5 3.75 3.75 0 0 0 0-7.5Z" />
    </svg>
    <img src="https://www.svgrepo.com/show/381213/dark-mode-night-moon.svg" alt="" aria-hidden="true"
        class="h-5 w-5 dark:hidden">
    @unless($compact)
        <span class="hidden sm:inline dark:hidden">Mode gelap</span>
        <span class="hidden sm:dark:inline">Mode terang</span>
    @endunless
</button>
