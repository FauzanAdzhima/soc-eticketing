@props([
    'current' => '',
    'items' => [],
])

@php
    $isAuthenticated = auth()->check();
    $homeRoute = $isAuthenticated ? route('dashboard') : route('home');
    $homeLabel = $isAuthenticated ? 'Dashboard' : 'Menu Utama';
@endphp

<nav aria-label="Breadcrumb" class="mb-5">
    <ol class="flex flex-wrap items-center gap-2 text-sm text-zinc-600 dark:text-zinc-300">
        <li>
            <a href="{{ $homeRoute }}" class="font-medium hover:text-zinc-900 dark:hover:text-zinc-100">
                {{ $homeLabel }}
            </a>
        </li>

        @foreach ($items as $item)
            <li aria-hidden="true" class="text-zinc-400">/</li>
            <li>
                <a href="{{ $item['href'] }}" class="font-medium hover:text-zinc-900 dark:hover:text-zinc-100">
                    {{ $item['label'] }}
                </a>
            </li>
        @endforeach

        @if (filled($current))
            <li aria-hidden="true" class="text-zinc-400">/</li>
            <li class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $current }}</li>
        @endif
    </ol>
</nav>
