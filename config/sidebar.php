<?php

return [
    'items' => [
        [
            'label' => 'Dashboard',
            'icon' => 'home',
            'route' => 'dashboard',
            'permission' => 'dashboard.view',
        ],
        [
            'label' => 'Pengaturan Profil',
            'icon' => 'cog-6-tooth',
            'route' => 'profile',
            'permission' => null,
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
