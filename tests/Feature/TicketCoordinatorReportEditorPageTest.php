<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\TicketCoordinatorReportEditorPage;
use App\Models\IncidentCategory;
use App\Models\IncidentResponseAction;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketReport;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketCoordinatorReportEditorPageTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_editor_handles_null_meta_and_export_print_flow(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');

        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeClosedVerifiedTicket();

        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
            'description' => 'Mitigasi awal dari responder',
            'meta' => null,
        ]);

        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_RECOVERY,
            'description' => 'Recovery lanjutan',
            'meta' => ['log-a', 'log-b'],
        ]);

        $this->actingAs($koordinator)
            ->get(route('tickets.reports.edit', $ticket))
            ->assertOk();

        $component = Livewire::actingAs($koordinator)
            ->test(TicketCoordinatorReportEditorPage::class, ['ticket' => $ticket])
            ->assertHasNoErrors();

        $ticketReport = TicketReport::query()->where('ticket_id', $ticket->id)->firstOrFail();
        $responseActions = is_array($ticketReport->snapshot_json['response_actions'] ?? null)
            ? $ticketReport->snapshot_json['response_actions']
            : [];

        $this->assertCount(2, $responseActions);
        $this->assertTrue(
            ! array_key_exists('meta', $responseActions[0]) || $responseActions[0]['meta'] === null
        );
        $this->assertTrue(
            ! array_key_exists('meta', $responseActions[1]) || $responseActions[1]['meta'] === null
        );

        $unsafeHtml = '<h2>Draft Uji</h2><p>Isi laporan koordinator<script>alert(1)</script></p>';

        $component
            ->set('bodyHtml', $unsafeHtml)
            ->call('saveDraft')
            ->assertHasNoErrors();

        $ticketReport->refresh();
        $this->assertSame(TicketReport::STATUS_DRAFT, $ticketReport->status);
        $this->assertStringContainsString('Draft Uji', (string) $ticketReport->body_markdown);
        $this->assertStringNotContainsString('<script>', (string) $ticketReport->body_markdown);

        $component
            ->call('exportPrint')
            ->assertRedirect(route('tickets.reports.print', [
                'ticket' => $ticket->public_id,
                'report' => $ticketReport->id,
            ]));

        $this->actingAs($koordinator)
            ->get(route('tickets.reports.print', [
                'ticket' => $ticket->public_id,
                'report' => $ticketReport->id,
            ]))
            ->assertOk()
            ->assertSee('Draft Uji', false)
            ->assertDontSee('alert(1)', false);
    }

    public function test_coordinator_can_upload_report_image_and_non_coordinator_cannot_access_it(): void
    {
        Storage::fake('local');

        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        $ticket = $this->makeClosedVerifiedTicket();

        $response = $this->actingAs($koordinator)->postJson(
            route('tickets.reports.images.store', ['ticket' => $ticket->public_id]),
            ['image' => UploadedFile::fake()->image('evidence.png')]
        );

        $response->assertOk()->assertJsonStructure(['url']);
        $url = (string) $response->json('url');

        $this->assertStringContainsString('/tickets/'.$ticket->public_id.'/reports/images/', $url);
        $storedFiles = Storage::disk('local')->allFiles('ticket-report-images/'.$ticket->public_id);
        $this->assertCount(1, $storedFiles);

        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $this->actingAs($pic)->get($url)->assertForbidden();
    }

    private function makeClosedVerifiedTicket(): Ticket
    {
        $org = Organization::query()->create(['name' => 'Org Report']);
        $cat = IncidentCategory::query()->create(['name' => 'Cat Report']);

        $creator = User::factory()->create();
        $creator->assignRole('pic');

        return Ticket::query()->create([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-REP-'.strtoupper(Str::random(6)),
            'title' => 'Ticket report editor test',
            'reporter_name' => 'Reporter',
            'reporter_email' => 'reporter@example.com',
            'reporter_organization_id' => $org->id,
            'reported_at' => now(),
            'incident_time' => now(),
            'incident_severity' => 'Low',
            'incident_description' => 'Deskripsi insiden uji',
            'incident_category_id' => $cat->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_CLOSED,
            'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
            'created_by' => $creator->id,
        ]);
    }
}
