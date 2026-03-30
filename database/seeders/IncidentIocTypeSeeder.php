<?php

namespace Database\Seeders;

use App\Models\IncidentIocType;
use Illuminate\Database\Seeder;

class IncidentIocTypeSeeder extends Seeder
{
    public function run(): void
    {
        $types = [
            ['ioc_type' => 'ip', 'description' => 'Alamat IP'],
            ['ioc_type' => 'domain', 'description' => 'Nama domain / hostname'],
            ['ioc_type' => 'hash', 'description' => 'Hash file (MD5, SHA256, dll.)'],
            ['ioc_type' => 'url', 'description' => 'URL / URI'],
        ];

        foreach ($types as $row) {
            IncidentIocType::query()->firstOrCreate(
                ['ioc_type' => $row['ioc_type']],
                ['description' => $row['description']]
            );
        }
    }
}
