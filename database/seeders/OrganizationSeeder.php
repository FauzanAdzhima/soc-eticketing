<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Organization;

class OrganizationSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Organization::create(['name' => 'Dinas Komunikasi dan Informatika']);
        Organization::create(['name' => 'BKD & Korpri']);
        Organization::create(['name' => 'BPSDM']);
        Organization::create(['name' => 'Biro Umum']);
    }
}
