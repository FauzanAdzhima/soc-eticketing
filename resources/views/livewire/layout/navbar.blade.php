<div
    class="sticky top-0 z-30 border-b border-primary-foreground/15 bg-primary px-4 py-3 text-primary-foreground shadow-sm">
    <div class="flex items-center justify-between gap-3">
        <div class="flex items-center gap-2">
            <button type="button" @click="$dispatch('toggle-sidebar-mobile')"
                class="inline-flex rounded-md p-2 text-primary-foreground hover:bg-primary-foreground/10 lg:hidden"
                aria-label="Toggle sidebar mobile">
                <svg class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M3 5.75A.75.75 0 0 1 3.75 5h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 5.75Zm0 4.25a.75.75 0 0 1 .75-.75h12.5a.75.75 0 0 1 0 1.5H3.75A.75.75 0 0 1 3 10Zm.75 3.5a.75.75 0 0 0 0 1.5h12.5a.75.75 0 0 0 0-1.5H3.75Z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <button type="button" @click="$dispatch('toggle-sidebar')"
                class="hidden rounded-md p-2 text-primary-foreground hover:bg-primary-foreground/10 lg:inline-flex"
                aria-label="Toggle sidebar collapse">
                <svg class="h-5 w-5 transition-transform duration-300" :class="sidebarCollapsed ? 'rotate-180' : ''"
                    viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                    <path fill-rule="evenodd"
                        d="M11.78 4.22a.75.75 0 0 1 0 1.06L7.06 10l4.72 4.72a.75.75 0 1 1-1.06 1.06l-5.25-5.25a.75.75 0 0 1 0-1.06l5.25-5.25a.75.75 0 0 1 1.06 0Z"
                        clip-rule="evenodd" />
                </svg>
            </button>

            <div>
                <p class="text-sm text-primary-foreground">SOC eTicketing</p>
                <h1 class="text-base font-semibold text-primary-foreground">{{ $this->userName }}</h1>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <flux:button type="submit" size="sm" variant="danger">Logout</flux:button>
            </form>
        </div>
    </div>
</div>
