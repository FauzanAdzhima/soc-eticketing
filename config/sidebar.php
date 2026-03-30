<?php

// Urutan grup operasional di sidebar mengikuti hierarki multi-role: PIC (ticket_ops) → Analis → Responder.
// Penyortiran akhir dilakukan di App\Livewire\Layout\Sidebar::sortMenusByOperationalHierarchy.

return [
    'items' => [
        [
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'dashboard',
            'group' => 'core',
            // Umum: semua pengguna terautentikasi (PIC, analis, admin, …) — selaras dengan route /dashboard (hanya auth).
            'permission' => null,
        ],
        [
            'label' => 'Profil',
            'icon' => 'user',
            'route' => 'profile',
            'group' => 'core',
            'permission' => null,
        ],
        [
            'label' => 'Daftar Tiket',
            'icon' => 'clipboard-document-list',
            'route' => 'tickets.index',
            'group' => 'ticket_ops',
            'permission' => 'ticket.view',
        ],
        [
            'label' => 'Analisis Tiket',
            'icon' => 'exclamation-triangle',
            'route' => 'tickets.index',
            'route_query' => ['scope' => 'analyst'],
            'group' => 'analyst_work',
            'permission' => 'ticket.analyze',
        ],
        [
            'label' => 'Penanganan Tiket',
            'icon' => 'bolt',
            'route' => 'tickets.index',
            'route_query' => ['scope' => 'responder'],
            'group' => 'responder_work',
            'permission' => 'ticket.respond',
        ],
        [
            'label' => 'Daftar User',
            'icon' => 'users',
            'route' => 'admin.users.index',
            'group' => 'admin_registry',
            'permission' => 'user.view',
        ],
        [
            'label' => 'Daftar Role',
            'icon' => 'shield-check',
            'route' => 'admin.roles.index',
            'group' => 'admin_registry',
            'permission' => 'role.view',
        ],
        [
            'label' => 'Daftar OPD',
            'icon' => 'building-office-2',
            'route' => 'admin.organizations.index',
            'group' => 'admin_registry',
            'permission' => 'opd.view',
        ],
        [
            'label' => 'Daftar Kategori Insiden',
            'icon' => 'exclamation-triangle',
            'route' => 'admin.incident-categories.index',
            'group' => 'admin_registry',
            'permission' => 'incident-category.view',
        ],
    ],
];
