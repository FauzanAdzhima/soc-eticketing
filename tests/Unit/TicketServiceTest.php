<?php

namespace Tests\Unit;

use Tests\TestCase;
use App\Mail\ReporterTicketCreatedMail;
use App\Services\TicketService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

class TicketServiceTest extends TestCase
{
    use RefreshDatabase; // This clears the database for each test

    public function test_it_creates_ticket_with_correct_severity()
    {
        $category = \App\Models\IncidentCategory::create(['name' => 'Web Defacement']);
        $service = new \App\Services\TicketService();

        $result = $service->createTicket([
            'title' => 'Test',
            'reporter_name' => 'Fauzan',
            'reporter_email' => 'fauzan@kepri.go.id',
            'incident_category_id' => $category->id,
            'incident_severity' => 'Critical', // Using the new column
            'incident_description' => 'Test Desc',
            'incident_time' => now(),
        ]);
        $ticket = $result->ticket;

        $this->assertNotNull($ticket->ticket_number);
        $this->assertStringStartsWith('TIC-', $ticket->ticket_number);
        $this->assertEquals(13, strlen($ticket->ticket_number)); // TIC-YYMM-XXXX

        $this->assertEquals('Critical', $ticket->incident_severity);
        $this->assertEquals(\App\Models\Ticket::STATUS_AWAITING_VERIFICATION, $ticket->status);
        $this->assertEquals(\App\Models\Ticket::REPORT_STATUS_PENDING, $ticket->report_status);
        $this->assertFalse($ticket->report_is_valid);
    }

    public function test_it_creates_ticket_with_multiple_evidences()
    {
        Storage::fake('public');

        $category = \App\Models\IncidentCategory::create([
            'name' => 'Malware',
            'slug' => 'test-malware-'.uniqid(),
        ]);
        $service = new \App\Services\TicketService();

        $result = $service->createTicket([
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
        $ticket = $result->ticket;

        $this->assertCount(2, $ticket->evidences);
        Storage::disk('public')->assertExists($ticket->evidences[0]->path);
        Storage::disk('public')->assertExists($ticket->evidences[1]->path);
    }

    public function test_it_queues_reporter_ticket_created_email()
    {
        Mail::fake();

        $category = \App\Models\IncidentCategory::create([
            'name' => 'Phishing',
            'slug' => 'test-phishing-'.uniqid(),
        ]);
        $service = new TicketService;
        $reporterEmail = 'pelapor@example.com';

        $result = $service->createTicket([
            'title' => 'Email mencurigakan',
            'reporter_name' => 'Budi',
            'reporter_email' => $reporterEmail,
            'incident_category_id' => $category->id,
            'incident_severity' => 'Medium',
            'incident_description' => 'Isi laporan.',
            'incident_time' => now(),
        ]);
        $ticket = $result->ticket;

        $persisted = $ticket->fresh();
        $this->assertMatchesRegularExpression('/^[a-f0-9]{64}$/', (string) $persisted->reporter_chat_token_hash);
        $this->assertNotNull($persisted->reporter_chat_token_created_at);

        Mail::assertQueued(ReporterTicketCreatedMail::class, function (ReporterTicketCreatedMail $mail) use ($reporterEmail, $ticket) {
            $body = $mail->render();
            $trackPathPrefix = '/tickets/track/'.$ticket->public_id.'/';

            return $mail->hasTo($reporterEmail)
                && $mail->ticket->is($ticket)
                && str_contains($mail->envelope()->subject, $ticket->ticket_number)
                && str_contains($body, $ticket->ticket_number)
                && str_contains($body, $trackPathPrefix);
        });
    }
}
