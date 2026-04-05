<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $permissions = [
            'dashboard.view',
            'user.view',
            'user.create',
            'user.update',
            'user.delete',
            'role.view',
            'role.create',
            'role.update',
            'role.delete',
            'opd.view',
            'opd.create',
            'opd.update',
            'opd.delete',
            'incident-category.view',
            'incident-category.create',
            'incident-category.update',
            'incident-category.delete',
            'ticket.create.public',
            'ticket.create.pic',
            'ticket.view',
            'ticket.view_all',
            'ticket.assign',
            'ticket.analyze',
            'ticket.respond',
            'ticket.update_status',
            'ticket.close',
            'ticket.validate_handling',
            'ticket.reopen_closed',
            'ticket.incident_report.manage',
            'ticket.chat.view',
            'ticket.chat.send_external',
            'ticket.chat.send_internal',
            'rbac.user_role.assign',
            'rbac.user_role.revoke',
            'rbac.user_role.audit',
        ];

        foreach ($permissions as $permission) {
            Permission::findOrCreate($permission, 'web');
        }

        $roleMatrix = [
            'admin' => [
                'desc' => 'Admin Sistem, full akses',
                'permissions' => $permissions,
            ],
            'pic' => [
                'desc' => 'Person in Charge, penerima laporan insiden',
                'permissions' => [
                    'ticket.create.pic',
                    'ticket.view',
                    'ticket.assign',
                    'ticket.chat.view',
                    'ticket.chat.send_external',
                    'ticket.chat.send_internal',
                ],
            ],
            'analis' => [
                'desc' => 'Analis Insiden, melakukan analisis insiden',
                'permissions' => [
                    'ticket.view',
                    'ticket.analyze',
                    'ticket.update_status',
                    'ticket.chat.view',
                    'ticket.chat.send_external',
                    'ticket.chat.send_internal',
                ],
            ],
            'responder' => [
                'desc' => 'Responder Insiden, melakukan penanganan insiden',
                'permissions' => [
                    'ticket.view',
                    'ticket.respond',
                    'ticket.update_status',
                    'ticket.chat.view',
                    'ticket.chat.send_external',
                    'ticket.chat.send_internal',
                ],
            ],
            'koordinator' => [
                'desc' => 'Koordinator Penanganan Insiden, mengkoordinasikan penanganan insiden',
                'permissions' => [
                    'ticket.view',
                    'ticket.view_all',
                    'ticket.assign',
                    'ticket.close',
                    'ticket.validate_handling',
                    'ticket.reopen_closed',
                    'ticket.incident_report.manage',
                    'ticket.chat.view',
                    'ticket.chat.send_external',
                    'ticket.chat.send_internal',
                ],
            ],
            'pimpinan' => [
                'desc' => 'Pimpinan Organisasi, menerima laporan penanganan insiden',
                'permissions' => [
                    'ticket.view',
                    'ticket.chat.view',
                ],
            ],
        ];

        foreach ($roleMatrix as $roleName => $config) {
            $role = Role::firstOrCreate(
                ['name' => $roleName, 'guard_name' => 'web'],
                ['desc' => $config['desc']]
            );

            $role->syncPermissions($config['permissions']);
        }
    }
}
