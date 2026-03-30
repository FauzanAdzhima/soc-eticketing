<?php

namespace Database\Seeders;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Services\TicketService;
use Illuminate\Database\Seeder;

/**
 * Tiket contoh status menunggu verifikasi PIC (Pending + Awaiting Verification).
 */
class DemoPendingTicketsSeeder extends Seeder
{
    public function run(): void
    {
        $categories = IncidentCategory::query()->orderBy('id')->get();
        if ($categories->isEmpty()) {
            $this->command?->warn('DemoPendingTicketsSeeder: tidak ada incident_categories, lewati.');

            return;
        }

        $organizationId = Organization::query()->value('id');

        $service = app(TicketService::class);

        $rows = [
            [
                'title' => 'Email phishing mengatasnamakan bank',
                'reporter_name' => 'Andi Wijaya',
                'reporter_email' => 'andi.wijaya@contoh.go.id',
                'reporter_phone' => '081298765432',
                'incident_severity' => 'High',
                'incident_description' => "Beberapa staf menerima email yang meniru domain resmi bank dan meminta klik tautan untuk 'konfirmasi akun'. Lampiran tidak dibuka; diminta pendampingan verifikasi.",
            ],
            [
                'title' => 'Akses tidak biasa ke portal internal',
                'reporter_name' => 'Rina Kusuma',
                'reporter_email' => 'rina.k@contoh.go.id',
                'reporter_phone' => null,
                'incident_severity' => 'Medium',
                'incident_description' => 'Log menunjukkan percobaan login dari IP asing di luar jam kerja. Akun terkunci sementara oleh sistem.',
            ],
            [
                'title' => 'Website OPD tampilan berubah (dugaan defacement)',
                'reporter_name' => 'Bagian TI Dinas X',
                'reporter_email' => 'ti@dinasx.contoh.go.id',
                'reporter_phone' => '0215550102',
                'incident_severity' => 'Critical',
                'incident_description' => 'Halaman utama menampilkan pesan asing. Server diputus sementara dari jaringan publik.',
            ],
            [
                'title' => 'File mencurigakan di share folder',
                'reporter_name' => 'Eko Prasetyo',
                'reporter_email' => 'eko.prasetyo@contoh.go.id',
                'reporter_phone' => '081211223344',
                'incident_severity' => 'Low',
                'incident_description' => 'User menemukan eksekusi .exe di folder bersama yang seharusnya hanya dokumen. File diisolasi, belum dijalankan.',
            ],
        ];

        foreach ($rows as $index => $row) {
            $category = $categories[$index % $categories->count()];
            $service->createTicket([
                'title' => $row['title'],
                'reporter_name' => $row['reporter_name'],
                'reporter_email' => $row['reporter_email'],
                'reporter_phone' => $row['reporter_phone'],
                'reporter_organization_id' => $organizationId,
                'reporter_organization_name' => null,
                'incident_category_id' => $category->id,
                'incident_severity' => $row['incident_severity'],
                'incident_description' => $row['incident_description'],
                'incident_time' => now()->subHours(6 * ($index + 1)),
                'created_by' => null,
                'evidence_files' => [],
            ]);
        }
    }
}
