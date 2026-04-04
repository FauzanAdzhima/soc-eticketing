<?php

use Illuminate\Support\Str;

if (! function_exists('role_label')) {
    /**
     * Display label for a Spatie role slug. Unknown slugs use a readable headline fallback.
     */
    function role_label(?string $name): string
    {
        if ($name === null || $name === '') {
            return '';
        }

        $labels = config('roles.labels', []);

        if (isset($labels[$name])) {
            return $labels[$name];
        }

        return Str::headline(str_replace(['-', '_'], ' ', $name));
    }
}

if (! function_exists('ticket_coordinator_badge_label')) {
    /**
     * Label UI untuk badge status koordinator. Kunci kanonikal sama dengan
     * nilai dari {@see \App\Models\Ticket::coordinatorBadge()} ['label'].
     */
    function ticket_coordinator_badge_label(string $canonicalLabel): string
    {
        if ($canonicalLabel === '' || $canonicalLabel === '—') {
            return $canonicalLabel;
        }

        $map = trans('tickets.coordinator_badge_labels', [], 'id');

        if (is_array($map) && isset($map[$canonicalLabel])) {
            return $map[$canonicalLabel];
        }

        return $canonicalLabel;
    }
}

if (! function_exists('ticket_severity_pill_classes')) {
    /**
     * Kelas Tailwind untuk pill nomor tiket berdasarkan incident_severity laporan.
     *
     * @return array{wrapper: string, severity_key: string|null}
     */
    function ticket_severity_pill_classes(?string $severity): array
    {
        $key = $severity !== null && $severity !== '' ? $severity : null;

        $wrapper = match ($key) {
            'Critical' => 'bg-red-100 text-red-900 ring-1 ring-inset ring-red-200 dark:bg-red-950/50 dark:text-red-100 dark:ring-red-800/60',
            'High' => 'bg-orange-100 text-orange-900 ring-1 ring-inset ring-orange-200 dark:bg-orange-950/45 dark:text-orange-100 dark:ring-orange-800/50',
            'Medium' => 'bg-amber-100 text-amber-900 ring-1 ring-inset ring-amber-200 dark:bg-amber-950/40 dark:text-amber-100 dark:ring-amber-800/50',
            'Low' => 'bg-emerald-100 text-emerald-900 ring-1 ring-inset ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-100 dark:ring-emerald-800/50',
            default => 'bg-zinc-100 text-zinc-700 ring-1 ring-inset ring-zinc-200 dark:bg-zinc-800 dark:text-zinc-200 dark:ring-zinc-600/60',
        };

        return ['wrapper' => $wrapper, 'severity_key' => $key];
    }
}
