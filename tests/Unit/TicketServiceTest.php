<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase; // This clears the database for each test

    public function test_it_creates_ticket_with_correct_severity()
    {
        $category = \App\Models\IncidentCategory::create(['name' => 'Web Defacement']);
        $service = new \App\Services\TicketService();

        $ticket = $service->createTicket([
            'title' => 'Test',
            'reporter_name' => 'Fauzan',
            'reporter_email' => 'fauzan@kepri.go.id',
            'incident_category_id' => $category->id,
            'incident_severity' => 'Critical', // Using the new column
            'incident_description' => 'Test Desc',
            'incident_time' => now(),
        ]);

        $this->assertNotNull($ticket->ticket_number);
        $this->assertStringStartsWith('TIC-', $ticket->ticket_number);
        $this->assertEquals(13, strlen($ticket->ticket_number)); // TIC-YYMM-XXXX

        $this->assertEquals('Critical', $ticket->incident_severity);
    }

    public function test_it_creates_ticket_with_multiple_evidences()
    {
        Storage::fake('public');

        $category = \App\Models\IncidentCategory::create(['name' => 'Malware']);
        $service = new \App\Services\TicketService();

        $ticket = $service->createTicket([
            'title' => 'Insiden malware',
            'reporter_name' => 'Pelapor',
            'reporter_email' => 'pelapor@example.com',
            'incident_category_id' => $category->id,
            'incident_severity' => 'High',
            'incident_description' => 'Terdapat file mencurigakan.',
            'incident_time' => now(),
            'reporter_organization_name' => 'Instansi Umum',
            'evidence_files' => [
                UploadedFile::fake()->image('screen-1.jpg'),
                UploadedFile::fake()->create('log.txt', 10, 'text/plain'),
            ],
        ]);

        $this->assertCount(2, $ticket->evidences);
        Storage::disk('public')->assertExists($ticket->evidences[0]->path);
        Storage::disk('public')->assertExists($ticket->evidences[1]->path);
    }
}
