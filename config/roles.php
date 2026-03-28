<?php

/**
 * Human-readable labels for Spatie role slugs (UI only). Technical `name` stays the source of truth for syncRoles and middleware.
 * Custom roles without an entry here fall back via role_label() to Str::headline().
 */
return [

    'labels' => [
        'admin' => 'Admin Sistem [Full Access]',
        'pic' => 'PIC [SOC Tier 1]',
        'analis' => 'Analis Keamanan [SOC Tier 2]',
        'responder' => 'Responder Insiden [SOC Tier 2/3]',
        'koordinator' => 'Koordinator Penanganan [SOC Tier 2]',
        'pimpinan' => 'Pimpinan / Chief [Stakeholder]',
    ],

];
