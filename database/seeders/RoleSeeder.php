<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Role::create(['name' => 'admin', 'desc' => 'Admin Sistem, full akses']);
        Role::create(['name' => 'pic', 'desc' => 'Person in Charge, penerima laporan insiden']);
        Role::create(['name' => 'analis', 'desc' => 'Analis Insiden, melakukan analisis insiden']);
        Role::create(['name' => 'responder', 'desc' => 'Responder Insiden, melakukan penanganan insiden']);
        Role::create(['name' => 'koordinator', 'desc' => 'Koordinator Penanganan Insiden, mengkoordinasikan penanganan insiden']);
        Role::create(['name' => 'pimpinan', 'desc' => 'Pimpinan Organisasi, menerima laporan penanganan insiden']);
    }
}
