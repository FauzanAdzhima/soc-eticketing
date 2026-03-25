<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OrganizationSeeder::class,
            RoleSeeder::class,
            IncidentCategorySeeder::class
            // UserSeeder::class,
        ]);

        $organizations = Organization::all();

        $admin = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $admin->assignRole('admin');

        $pic = User::factory()->create([
            'name' => 'PIC',
            'email' => 'pic@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $pic->assignRole('pic');

        $analis = User::factory()->create([
            'name' => 'Analis',
            'email' => 'analis@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $analis->assignRole('analis');

        $responder = User::factory()->create([
            'name' => 'Responder',
            'email' => 'responder@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $responder->assignRole('responder');

        $koordinator = User::factory()->create([
            'name' => 'Koordinator',
            'email' => 'koordinator@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $koordinator->assignRole('koordinator');

        $pimpinan = User::factory()->create([
            'name' => 'Pimpinan',
            'email' => 'manager@test.com',
            'organization_id' => $organizations->first()->id,
        ]);
        $pimpinan->assignRole('pimpinan');

        // User::factory(3)
        //     ->recycle($organizations)
        //     ->create()
        //     ->each(function ($user) {
        //         $user->assignRole(Role::all()->random());
        //     });
    }
}
