@php
    $iconSvgs = [
        'home' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M3 10.5 12 3l9 7.5" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><path d="M5.25 9.75v9A1.5 1.5 0 0 0 6.75 20.25h10.5a1.5 1.5 0 0 0 1.5-1.5v-9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'user' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M15.75 6.75a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" stroke="currentColor" stroke-width="1.8"/><path d="M4.5 19.25a7.5 7.5 0 0 1 15 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'cog-6-tooth' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M10.5 3.75h3l.48 2.05a6.9 6.9 0 0 1 1.54.89l1.93-.86 2.12 2.12-.86 1.93c.34.48.64 1 .89 1.54l2.05.48v3l-2.05.48a6.9 6.9 0 0 1-.89 1.54l.86 1.93-2.12 2.12-1.93-.86a6.9 6.9 0 0 1-1.54.89l-.48 2.05h-3l-.48-2.05a6.9 6.9 0 0 1-1.54-.89l-1.93.86-2.12-2.12.86-1.93a6.9 6.9 0 0 1-.89-1.54l-2.05-.48v-3l2.05-.48a6.9 6.9 0 0 1 .89-1.54l-.86-1.93 2.12-2.12 1.93.86c.48-.34 1-.64 1.54-.89l.48-2.05Z" stroke="currentColor" stroke-width="1.4" stroke-linejoin="round"/><circle cx="12" cy="12" r="2.75" stroke="currentColor" stroke-width="1.8"/></svg>',
        'clipboard-document-list' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M9 5.25h6l.75 1.5H19.5a1.5 1.5 0 0 1 1.5 1.5v11.25a1.5 1.5 0 0 1-1.5 1.5h-15A1.5 1.5 0 0 1 3 19.25V8.25A1.5 1.5 0 0 1 4.5 6.75H8.25L9 5.25Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M9.75 12h4.5M9.75 15.75h4.5M9.75 9.75h2.25" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'users' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M16.5 7.5a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" stroke="currentColor" stroke-width="1.8"/><path d="M3.75 18a6.75 6.75 0 0 1 13.5 0" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/><path d="M18 9.75a2.25 2.25 0 1 0 0 4.5M19.5 18a5.5 5.5 0 0 0-2.25-4.43" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
        'shield-check' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="m12 3 7.5 3v5.25c0 4.42-2.85 8.43-7.5 9.75-4.65-1.32-7.5-5.33-7.5-9.75V6L12 3Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="m9.25 12 1.9 1.9 3.6-3.6" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'building-office-2' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M3.75 20.25h16.5M6 20.25v-12l6-3 6 3v12M9 9.75h.01M12 9.75h.01M15 9.75h.01M9 13.5h.01M12 13.5h.01M15 13.5h.01M11.25 20.25v-3h1.5v3" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>',
        'exclamation-triangle' =>
            '<svg viewBox="0 0 24 24" fill="none" aria-hidden="true" class="h-4 w-4"><path d="M10.29 3.86 1.82 18a2 2 0 0 0 1.72 3h16.92a2 2 0 0 0 1.72-3l-8.47-14.14a2 2 0 0 0-3.42 0Z" stroke="currentColor" stroke-width="1.8" stroke-linejoin="round"/><path d="M12 9v4.5M12 17.25h.01" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>',
    ];
@endphp

<aside class="flex h-full w-full flex-col border-r border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
    <div class="p-4">
        <h2 class="text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400" x-show="!sidebarCollapsed"
            x-cloak>Navigation</h2>
        <h2 class="text-center text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:text-zinc-400"
            x-show="sidebarCollapsed" x-cloak>Nav</h2>
    </div>

    <nav class="flex-1 space-y-1 overflow-y-auto px-2 pb-4">
        @forelse ($menus as $index => $menu)
            @if ($index > 0)
                @php
                    $prevMenu = $menus[$index - 1];
                    $showSectionDivider = blank($prevMenu['permission'] ?? null) && filled($menu['permission'] ?? null);
                @endphp
                @if ($showSectionDivider)
                    <div role="separator" aria-hidden="true"
                        class="my-2 border-t border-zinc-200 dark:border-zinc-700"></div>
                @endif
            @endif
            @if (blank($menu['permission']))
                <a href="{{ route($menu['route']) }}"
                    x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                    @class([
                        'relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                        'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $this->isActive($menu['route']),
                        'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' => !$this->isActive($menu['route']),
                    ])>
                    <span class="flex w-8 justify-center text-zinc-400">{!! $iconSvgs[$menu['icon']] ?? strtoupper(substr($menu['label'], 0, 1)) !!}</span>
                    <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ $menu['label'] }}</span>
                    <span x-cloak x-show="sidebarCollapsed && hover" x-transition
                        class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-xs font-medium text-white shadow-md lg:block dark:bg-zinc-700">
                        {{ $menu['label'] }}
                    </span>
                </a>
            @else
                @can($menu['permission'])
                    <a href="{{ route($menu['route']) }}"
                        x-data="{ hover: false }" @mouseenter="hover = true" @mouseleave="hover = false"
                        @class([
                            'relative flex items-center gap-2 rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-zinc-900 text-white dark:bg-zinc-100 dark:text-zinc-900' => $this->isActive($menu['route']),
                            'text-zinc-700 hover:bg-zinc-100 dark:text-zinc-200 dark:hover:bg-zinc-800' => !$this->isActive($menu['route']),
                        ])>
                        <span class="flex w-8 justify-center text-zinc-400">{!! $iconSvgs[$menu['icon']] ?? strtoupper(substr($menu['label'], 0, 1)) !!}</span>
                        <span class="truncate" x-show="!sidebarCollapsed" x-cloak>{{ $menu['label'] }}</span>
                        <span x-cloak x-show="sidebarCollapsed && hover" x-transition
                            class="pointer-events-none absolute left-full top-1/2 z-50 ml-2 hidden -translate-y-1/2 whitespace-nowrap rounded-md bg-zinc-900 px-2 py-1 text-xs font-medium text-white shadow-md lg:block dark:bg-zinc-700">
                            {{ $menu['label'] }}
                        </span>
                    </a>
                @endcan
            @endif
        @empty
            <p class="px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400">Tidak ada menu yang tersedia.</p>
        @endforelse
    </nav>
</aside>
