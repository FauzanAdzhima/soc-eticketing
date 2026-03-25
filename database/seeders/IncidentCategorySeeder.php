<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class IncidentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            ['name' => 'Web Defacement', 'slug' => 'defacement'],
            ['name' => 'DDoS Attack', 'slug'=> 'ddos'],
            ['name' => 'Malware Infection', 'slug' => 'malware'],
            ['name' => 'Ransomware Attack', 'slug' => 'ransomware'],
            ['name' => 'Phishing', 'slug' => 'phishing']
        ];

        foreach ($categories as $cat) {
            IncidentCategory::create($cat);
        }
    }
}
