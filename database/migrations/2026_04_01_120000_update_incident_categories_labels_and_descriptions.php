<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
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

        foreach ($categories as $category) {
            $now = now();
            $existing = DB::table('incident_categories')->where('slug', $category['slug'])->first();

            if ($existing) {
                DB::table('incident_categories')
                    ->where('slug', $category['slug'])
                    ->update([
                        'name' => $category['name'],
                        'description' => $category['description'],
                        'updated_at' => $now,
                    ]);
            } else {
                DB::table('incident_categories')->insert([
                    'name' => $category['name'],
                    'slug' => $category['slug'],
                    'description' => $category['description'],
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('incident_categories')
            ->where('slug', 'phishing')
            ->update([
                'name' => 'Phishing',
                'description' => null,
                'updated_at' => now(),
            ]);
    }
};
