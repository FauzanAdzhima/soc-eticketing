<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Str;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'public_id' => Str::uuid(),
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('admin');

        User::create([
            'public_id' => Str::uuid(),
            'name' => 'PIC',
            'email' => 'pic@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('pic');

        User::create([
            'public_id' => Str::uuid(),
            'name' => 'Analis',
            'email' => 'analis@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('analis');

        User::create([
            'public_id' => Str::uuid(),
            'name' => 'Responder',
            'email' => 'responder@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('responder');

        User::create([
            'public_id' => Str::uuid(),
            'name' => 'Koordinator',
            'email' => 'koordinator@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('koordinator');

        User::create([
            'public_id' => Str::uuid(),
            'name' => 'Pimpinan',
            'email' => 'manager@test.com',
            'password' => bcrypt('password'),
        ])->assignRole('pimpinan');
    }
}
