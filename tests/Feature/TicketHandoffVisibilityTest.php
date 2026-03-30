<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\IndexPage;
use App\Models\IncidentAnalysis;
use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketHandoffVisibilityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_responder_queue_lists_ticket_in_analysis_phase_when_assigned_with_analysis(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_ANALYSIS,
        ]);
        $this->addAnalysis($ticket, $pic);

        Livewire::actingAs($responder)
            ->test(IndexPage::class, ['scope' => 'responder'])
            ->assertSee($ticket->ticket_number);
    }

    public function test_analyst_stays_on_analyst_queue_after_handoff_to_responder(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeOnProgressTicket($pic, $analis, [
            'sub_status' => Ticket::SUB_STATUS_ANALYSIS,
        ]);
        $this->addAnalysis($ticket, $analis);

        Livewire::actingAs($pic)
            ->test(IndexPage::class)
            ->set('detailTicketPublicId', $ticket->public_id)
            ->set('assignResponderUserId', (string) $responder->id)
            ->call('assignHandoffResponder')
            ->assertHasNoErrors();

        $this->assertTrue(
            TicketAssignment::query()
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $analis->id)
                ->where('kind', TicketAssignment::KIND_CONTRIBUTOR)
                ->where('is_active', true)
                ->exists()
        );

        Livewire::actingAs($analis)
            ->test(IndexPage::class, ['scope' => 'analyst'])
            ->assertSee($ticket->ticket_number);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOnProgressTicket(User $pic, User $assignee, array $overrides = []): Ticket
    {
        $org = Organization::query()->create(['name' => 'Org H']);
        $cat = IncidentCategory::query()->create(['name' => 'Cat H']);

        $ticket = Ticket::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-H-'.strtoupper(Str::random(5)),
            'title' => 'Ticket handoff visibility test',
            'reporter_name' => 'T',
            'reporter_email' => 't@example.com',
            'reporter_organization_id' => $org->id,
            'reported_at' => now(),
            'incident_time' => now(),
            'incident_severity' => 'Low',
            'incident_description' => 'd',
            'incident_category_id' => $cat->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_RESPONSE,
            'created_by' => $pic->id,
        ], $overrides));

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        return $ticket->fresh();
    }

    private function addAnalysis(Ticket $ticket, User $performer): void
    {
        IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $performer->id,
            'severity' => 'High',
            'impact' => 'impact',
            'root_cause' => 'rc',
            'recommendation' => 'rec',
        ]);
    }
}
