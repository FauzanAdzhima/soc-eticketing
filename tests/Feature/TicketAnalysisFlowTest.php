<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\TicketAnalysisPage;
use App\Models\IncidentAnalysis;
use App\Models\IncidentIocType;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\User;
use Database\Seeders\IncidentIocTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketAnalysisFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(IncidentIocTypeSeeder::class);
    }

    public function test_analyst_cannot_open_analysis_page_without_assignment(): void
    {
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $ticket = $this->makeOnProgressTicket($pic, assignedTo: null);

        $this->actingAs($analis)->get(route('tickets.analysis', $ticket))->assertForbidden();
    }

    public function test_analyst_can_submit_analysis_and_iocs_via_livewire(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeOnProgressTicket($pic, $analis->id);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        Livewire::actingAs($analis)
            ->test(TicketAnalysisPage::class, ['ticket' => $ticket])
            ->set('severity', 'High')
            ->set('impact', 'Layanan terganggu')
            ->set('root_cause', 'Kompromi kredensial')
            ->set('recommendation', 'Rotasi password')
            ->set('analysisSummary', 'Ringkasan singkat')
            ->set('iocRows', [
                ['type_id' => (string) $ipType->id, 'value' => '192.0.2.10', 'description' => 'C2'],
            ])
            ->call('submit')
            ->assertHasNoErrors()
            ->assertRedirect(route('tickets.index', [
                'scope' => 'analyst',
                'ticket' => $ticket->public_id,
            ]));

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_ANALYSIS, $ticket->sub_status);
        $this->assertSame(Ticket::STATUS_ON_PROGRESS, $ticket->status);

        $this->assertDatabaseHas('incident_analyses', [
            'ticket_id' => $ticket->id,
            'performed_by' => $analis->id,
            'severity' => 'High',
        ]);

        $analysisId = (int) \App\Models\IncidentAnalysis::query()->where('ticket_id', $ticket->id)->value('id');
        $this->assertDatabaseHas('incident_ioc', [
            'analysis_id' => $analysisId,
            'incident_ioc_type_id' => $ipType->id,
            'value' => '192.0.2.10',
        ]);

        $this->assertTrue(
            TicketLog::query()
                ->where('ticket_id', $ticket->id)
                ->where('action', 'sub_status_updated')
                ->where('user_id', $analis->id)
                ->exists()
        );
    }

    public function test_second_submit_without_addendum_updates_same_analysis_row(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeOnProgressTicket($pic, $analis->id);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        Livewire::actingAs($analis)
            ->test(TicketAnalysisPage::class, ['ticket' => $ticket])
            ->set('severity', 'High')
            ->set('impact', 'A')
            ->set('root_cause', 'B')
            ->set('recommendation', 'C')
            ->set('saveAsAddendum', false)
            ->set('iocRows', [
                ['type_id' => (string) $ipType->id, 'value' => '192.0.2.1', 'description' => ''],
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, IncidentAnalysis::query()->where('ticket_id', $ticket->id)->count());
        $firstId = (int) IncidentAnalysis::query()->where('ticket_id', $ticket->id)->value('id');

        Livewire::actingAs($analis)
            ->test(TicketAnalysisPage::class, ['ticket' => $ticket])
            ->set('severity', 'Critical')
            ->set('impact', 'A2')
            ->set('root_cause', 'B2')
            ->set('recommendation', 'C2')
            ->set('saveAsAddendum', false)
            ->set('iocRows', [
                ['type_id' => (string) $ipType->id, 'value' => '192.0.2.99', 'description' => ''],
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(1, IncidentAnalysis::query()->where('ticket_id', $ticket->id)->count());
        $this->assertSame($firstId, (int) IncidentAnalysis::query()->where('ticket_id', $ticket->id)->value('id'));
        $this->assertDatabaseHas('incident_analyses', [
            'id' => $firstId,
            'severity' => 'Critical',
        ]);
        $this->assertDatabaseMissing('incident_ioc', [
            'analysis_id' => $firstId,
            'value' => '192.0.2.1',
        ]);
        $this->assertDatabaseHas('incident_ioc', [
            'analysis_id' => $firstId,
            'value' => '192.0.2.99',
        ]);
    }

    public function test_submit_with_addendum_creates_second_analysis_row(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeOnProgressTicket($pic, $analis->id);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        Livewire::actingAs($analis)
            ->test(TicketAnalysisPage::class, ['ticket' => $ticket])
            ->set('severity', 'Low')
            ->set('impact', 'X')
            ->set('root_cause', 'Y')
            ->set('recommendation', 'Z')
            ->set('saveAsAddendum', false)
            ->set('iocRows', [
                ['type_id' => (string) $ipType->id, 'value' => '10.0.0.1', 'description' => ''],
            ])
            ->call('submit')
            ->assertHasNoErrors();

        Livewire::actingAs($analis)
            ->test(TicketAnalysisPage::class, ['ticket' => $ticket])
            ->set('severity', 'Medium')
            ->set('impact', 'X2')
            ->set('root_cause', 'Y2')
            ->set('recommendation', 'Z2')
            ->set('saveAsAddendum', true)
            ->set('iocRows', [
                ['type_id' => (string) $ipType->id, 'value' => '10.0.0.2', 'description' => ''],
            ])
            ->call('submit')
            ->assertHasNoErrors();

        $this->assertSame(2, IncidentAnalysis::query()->where('ticket_id', $ticket->id)->count());
    }

    public function test_guest_is_redirected_from_analysis_route(): void
    {
        $ticket = $this->makeBareTicket();

        $this->get(route('tickets.analysis', $ticket))->assertRedirect();
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeBareTicket(array $overrides = []): Ticket
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $org = \App\Models\Organization::query()->create(['name' => 'Org Test']);
        $cat = \App\Models\IncidentCategory::query()->create(['name' => 'Cat']);

        return Ticket::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-TEST-'.strtoupper(Str::random(4)),
            'title' => 'Ticket test',
            'reporter_name' => 'Tester',
            'reporter_email' => 'tester@example.com',
            'reporter_organization_id' => $org->id,
            'reported_at' => now(),
            'incident_time' => now(),
            'incident_severity' => 'Low',
            'incident_description' => 'desc',
            'incident_category_id' => $cat->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_TRIAGE,
            'created_by' => $pic->id,
        ], $overrides));
    }

    private function makeOnProgressTicket(User $pic, ?int $assignedTo): Ticket
    {
        $ticket = $this->makeBareTicket(['created_by' => $pic->id]);
        if ($assignedTo !== null) {
            TicketAssignment::create([
                'ticket_id' => $ticket->id,
                'user_id' => $assignedTo,
                'is_active' => true,
                'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
            ]);
        }

        return $ticket;
    }
}
