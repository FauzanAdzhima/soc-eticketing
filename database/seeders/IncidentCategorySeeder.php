<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use Illuminate\Database\Seeder;

class IncidentCategorySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $categories = [
            [
                'name' => 'Web Defacement',
                'slug' => 'defacement',
                'description' => 'Perubahan tampilan atau konten website tanpa izin, biasanya akibat kompromi akun/admin panel.',
            ],
            [
                'name' => 'DDoS Attack',
                'slug' => 'ddos',
                'description' => 'Serangan pembanjiran trafik ke layanan hingga sistem menjadi lambat atau tidak dapat diakses.',
            ],
            [
                'name' => 'Malware Infection',
                'slug' => 'malware',
                'description' => 'Infeksi perangkat oleh perangkat lunak berbahaya seperti trojan, worm, spyware, atau backdoor.',
            ],
            [
                'name' => 'Ransomware Attack',
                'slug' => 'ransomware',
                'description' => 'Serangan yang mengenkripsi data/sistem korban dan menuntut tebusan untuk proses pemulihan.',
            ],
            [
                'name' => 'Email Phishing',
                'slug' => 'phishing',
                'description' => 'Upaya penipuan melalui email palsu untuk mencuri kredensial, data sensitif, atau mengarahkan ke tautan berbahaya.',
            ],
        ];

        foreach ($categories as $cat) {
            IncidentCategory::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                ]
            );
        }
    }
}
