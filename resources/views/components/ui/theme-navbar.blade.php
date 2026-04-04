<nav {{ $attributes->merge([
    'class' =>
        'border-b border-border bg-surface/80 backdrop-blur-md transition-colors',
]) }}>
    <div
        class="mx-auto flex h-14 max-w-7xl items-center justify-between gap-4 px-4 sm:px-6 lg:px-8">
        <div class="flex min-w-0 flex-1 items-center gap-6">
            @isset($brand)
                <div class="shrink-0 text-foreground">
                    {{ $brand }}
                </div>
            @endisset

            <div class="flex min-w-0 flex-1 items-center gap-1 text-foreground md:gap-6">
                {{ $slot }}
            </div>
        </div>

        @isset($actions)
            <div class="flex shrink-0 items-center gap-2">
                {{ $actions }}
            </div>
        @endisset
    </div>
</nav>
