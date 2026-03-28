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
