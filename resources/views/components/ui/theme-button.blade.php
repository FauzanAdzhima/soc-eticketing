@props([
    'variant' => 'primary',
    'type' => 'button',
])

@php
    $base =
        'inline-flex items-center justify-center rounded-lg px-4 py-2 text-sm font-medium transition-colors disabled:pointer-events-none disabled:opacity-50';

    $variants = [
        'primary' =>
            'bg-primary text-primary-foreground hover:bg-primary-hover active:bg-primary-active',
        'secondary' => 'border border-border bg-secondary text-secondary-foreground hover:opacity-90',
        'ghost' => 'border border-border bg-transparent text-foreground hover:bg-surface',
    ];

    $variantClasses = $variants[$variant] ?? $variants['primary'];
@endphp

<button type="{{ $type }}" {{ $attributes->merge(['class' => $base . ' ' . $variantClasses]) }}>
    {{ $slot }}
</button>
