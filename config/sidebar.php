<?php

return [
    'items' => [
        [
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'dashboard',
            // Umum: semua pengguna terautentikasi (PIC, analis, admin, …) — selaras dengan route /dashboard (hanya auth).
            'permission' => null,
        ],
        [
            'label' => 'Profil',
            'icon' => 'user',
            'route' => 'profile',
            'permission' => null,
        ],
        [
            'label' => 'Daftar Tiket',
            'icon' => 'clipboard-document-list',
            'route' => 'tickets.index',
            'permission' => 'ticket.view',
        ],
        [
            'label' => 'Daftar User',
            'icon' => 'users',
            'route' => 'admin.users.index',
            'permission' => 'user.view',
        ],
        [
            'label' => 'Daftar Role',
            'icon' => 'shield-check',
            'route' => 'admin.roles.index',
            'permission' => 'role.view',
        ],
        [
            'label' => 'Daftar OPD',
            'icon' => 'building-office-2',
            'route' => 'admin.organizations.index',
            'permission' => 'opd.view',
        ],
        [
            'label' => 'Daftar Kategori Insiden',
            'icon' => 'exclamation-triangle',
            'route' => 'admin.incident-categories.index',
            'permission' => 'incident-category.view',
        ],
    ],
];
