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
            'route' => 'profile.edit',
            'permission' => null,
        ],
    ],
];
