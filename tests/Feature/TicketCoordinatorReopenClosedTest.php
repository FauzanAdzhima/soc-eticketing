<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\IndexPage;
use App\Models\IncidentAnalysis;
use App\Models\IncidentResponseAction;
use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketCoordinatorReopenClosedTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private IncidentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);

        $this->organization = Organization::create(['name' => 'Org Reopen']);
        $this->category = IncidentCategory::create(['name' => 'ReopenCat']);
    }

    public function test_koordinator_reopen_closed_requires_reason_and_writes_log_and_badge_flow(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');

        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $analysisPerformer = User::factory()->create();
        $analysisPerformer->assignRole('analis');

        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeClosedTicket([
            'created_by' => $pic->id,
        ]);

        IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $analysisPerformer->id,
            'severity' => 'High',
            'impact' => 'dampak',
            'root_cause' => 'akar',
            'recommendation' => 'rekomendasi',
        ]);

        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
            'description' => 'Tindakan sebelum selesai',
            'meta' => [],
        ]);

        $reason = 'Pengujian alasan reopen untuk badge lifecycle dan audit log.';
        $this->assertGreaterThanOrEqual(15, mb_strlen($reason));

        Livewire::actingAs($koordinator)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->set('reopenReason', $reason)
            ->call('reopenClosedByCoordinator')
            ->assertHasNoErrors();

        $ticket->refresh();

        $this->assertNotNull($ticket->reopened_at);
        $this->assertNull($ticket->handling_validated_at);
        $this->assertSame(Ticket::STATUS_ON_PROGRESS, $ticket->status);
        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $ticket->sub_status);

        $log = TicketLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('action', 'ticket_reopened')
            ->latest()
            ->first();
        $this->assertNotNull($log, 'Ticket log ticket_reopened harus ada.');

        $logData = json_decode((string) $log->data, true) ?: [];
        $this->assertSame($reason, $logData['reason'] ?? null);

        $badge = $ticket->coordinatorBadge();
        $this->assertSame('Reopened', $badge['label']);

        // Simulasikan transisi ke Resolution sebelum validasi handling.
        $ticket->updateSubStatus(Ticket::SUB_STATUS_RESOLUTION, $koordinator, true);
        $ticket->refresh();

        $ticket->validateHandling($koordinator);
        $ticket->refresh();

        $this->assertNotNull($ticket->handling_validated_at);
        $this->assertNull($ticket->reopened_at);
        $badgeAfterValidated = $ticket->coordinatorBadge();
        $this->assertSame('Validated', $badgeAfterValidated['label']);

        $ticket->close($koordinator);
        $ticket->refresh();

        $badgeAfterClosed = $ticket->coordinatorBadge();
        $this->assertSame('Closed', $badgeAfterClosed['label']);
        $this->assertSame(Ticket::STATUS_CLOSED, $ticket->status);
    }

    public function test_koordinator_reopen_closed_does_not_change_state_when_reason_too_short(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');

        $analysisPerformer = User::factory()->create();
        $analysisPerformer->assignRole('analis');

        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeClosedTicket();

        IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $analysisPerformer->id,
            'severity' => 'Low',
            'impact' => 'impact',
            'root_cause' => 'rc',
            'recommendation' => 'rec',
        ]);

        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
            'description' => 'before',
            'meta' => [],
        ]);

        $shortReason = 'kurang';

        Livewire::actingAs($koordinator)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->set('reopenReason', $shortReason)
            ->call('reopenClosedByCoordinator')
            ->assertHasErrors();

        $ticket->refresh();
        $this->assertNull($ticket->reopened_at);

        $this->assertFalse(
            TicketLog::query()
                ->where('ticket_id', $ticket->id)
                ->where('action', 'ticket_reopened')
                ->exists()
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeClosedTicket(array $overrides = []): Ticket
    {
        $creator = $overrides['created_by'] ?? User::factory()->create()->id;

        return Ticket::query()->create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-REOPEN-'.strtoupper(Str::random(6)),
            'title' => 'Ticket reopen test',
            'reporter_name' => 'Reporter',
            'reporter_email' => 'reporter@example.com',
            'reporter_organization_id' => $this->organization->id,
            'reported_at' => now(),
            'incident_time' => now(),
            'incident_severity' => 'Low',
            'incident_description' => 'desc',
            'incident_category_id' => $this->category->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_CLOSED,
            'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
            'created_by' => $creator,
        ], $overrides));
    }
}

