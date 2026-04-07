<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\IndexPage;
use App\Livewire\Pages\Tickets\TicketRespondPage;
use App\Models\IncidentAnalysis;
use App\Models\IncidentResponseAction;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\User;
use App\Services\IncidentResponseService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketRespondFlowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_responder_only_cannot_view_ticket_without_analysis_even_when_assigned(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_RESPONSE,
        ]);

        $this->actingAs($responder);
        $this->assertFalse(Gate::forUser($responder)->allows('view', $ticket));
    }

    public function test_responder_only_can_view_ticket_when_assigned_with_analysis_and_allowed_sub_status(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_RESPONSE,
        ]);
        $this->addAnalysis($ticket, $pic);

        $this->actingAs($responder);
        $this->assertTrue(Gate::forUser($responder)->allows('view', $ticket));
        $this->assertTrue(Gate::forUser($responder)->allows('respond', $ticket));
    }

    public function test_analyst_cannot_open_respond_route_without_respond_permission(): void
    {
        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $analis, ['sub_status' => Ticket::SUB_STATUS_RESPONSE]);
        $this->addAnalysis($ticket, $analis);

        $this->actingAs($analis)->get(route('tickets.respond', $ticket))->assertForbidden();
    }

    public function test_responder_can_open_respond_page_when_assigned(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $responder, ['sub_status' => Ticket::SUB_STATUS_RESPONSE]);
        $this->addAnalysis($ticket, $pic);

        $this->actingAs($responder)->get(route('tickets.respond', $ticket))->assertOk();
    }

    public function test_responder_can_start_response_handling_from_analysis_then_save_action_via_livewire(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_ANALYSIS,
        ]);
        $this->addAnalysis($ticket, $pic);

        $this->assertTrue(Gate::forUser($responder)->allows('beginResponseHandling', $ticket->fresh()));

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket])
            ->call('startResponseHandling')
            ->assertHasNoErrors()
            ->set('actionType', IncidentResponseAction::TYPE_MITIGATION)
            ->set('description', 'Memblokir alamat IP di firewall perimeter.')
            ->call('saveAction')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $ticket->sub_status);

        $subStatusLog = TicketLog::query()
            ->where('ticket_id', $ticket->id)
            ->where('action', 'sub_status_updated')
            ->where('user_id', $responder->id)
            ->orderByDesc('id')
            ->first();
        $this->assertNotNull($subStatusLog);
        $logData = json_decode((string) $subStatusLog->data, true) ?: [];
        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $logData['to'] ?? null);
        $this->assertSame(Ticket::SUB_STATUS_ANALYSIS, $logData['from'] ?? null);

        $this->assertDatabaseHas('incident_response_actions', [
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
        ]);
    }

    public function test_responder_cannot_start_response_handling_when_sub_status_already_response(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_RESPONSE,
        ]);
        $this->addAnalysis($ticket, $pic);

        $this->assertFalse(Gate::forUser($responder)->allows('beginResponseHandling', $ticket->fresh()));

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket])
            ->call('startResponseHandling')
            ->assertForbidden();
    }

    public function test_responder_can_store_action_and_mark_resolved_via_livewire(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $responder, ['sub_status' => Ticket::SUB_STATUS_RESPONSE]);
        $this->addAnalysis($ticket, $pic);

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket])
            ->set('actionType', IncidentResponseAction::TYPE_MITIGATION)
            ->set('description', 'Memblokir alamat IP di firewall perimeter.')
            ->call('saveAction')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('incident_response_actions', [
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
        ]);

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket->fresh()])
            ->call('markResolved')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_RESOLUTION, $ticket->sub_status);
        $this->assertTrue($ticket->isClosed());

        $this->assertTrue(
            TicketLog::query()
                ->where('ticket_id', $ticket->id)
                ->where('action', 'response_marked_resolved')
                ->exists()
        );

        $this->actingAs($responder);
        $this->get(route('tickets.respond', $ticket))->assertForbidden();
    }

    public function test_koordinator_reopens_response_phase_and_responder_can_record_again(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');

        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_RESPONSE,
        ]);
        $this->addAnalysis($ticket, $pic);
        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
            'description' => 'Tindakan sebelum selesai',
        ]);

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket->fresh()])
            ->call('markResolved')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertTrue($ticket->isClosed());

        $this->assertFalse(Gate::forUser($koordinator)->allows('reopenResponseRecording', $ticket->fresh()));
        $this->assertTrue(Gate::forUser($koordinator)->allows('reopenClosed', $ticket->fresh()));
        $this->assertFalse(Gate::forUser($responder)->allows('reopenClosed', $ticket->fresh()));

        Livewire::actingAs($koordinator)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->set(
                'reopenReason',
                'Memerlukan dokumentasi tindakan tambahan setelah peninjauan koordinator atas penutupan responder.'
            )
            ->call('reopenClosedByCoordinator')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $ticket->sub_status);

        Livewire::actingAs($responder)
            ->test(TicketRespondPage::class, ['ticket' => $ticket])
            ->set('actionType', IncidentResponseAction::TYPE_RECOVERY)
            ->set('description', 'Catatan tindakan setelah koordinator membuka kembali fase respons.')
            ->call('saveAction')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('incident_response_actions', [
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_RECOVERY,
        ]);
    }

    public function test_pic_sees_assign_responder_handoff_when_ticket_analyzed_on_progress(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeOnProgressTicket($pic, $responder, [
            'sub_status' => Ticket::SUB_STATUS_ANALYSIS,
        ]);
        $this->addAnalysis($ticket, $responder);

        $this->actingAs($pic);
        $this->assertTrue(Gate::forUser($pic)->allows('assignResponderHandoff', $ticket->fresh()));
    }

    public function test_pic_cannot_assign_responder_handoff_without_analysis(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeOnProgressTicket($pic, $analis, [
            'sub_status' => Ticket::SUB_STATUS_ANALYSIS,
        ]);

        $this->actingAs($pic);
        $this->assertFalse(Gate::forUser($pic)->allows('assignResponderHandoff', $ticket));
    }

    public function test_responder_not_assigned_gets_forbidden_on_record_via_service(): void
    {
        $responder = User::factory()->create();
        $responder->assignRole('responder');
        $other = User::factory()->create();
        $other->assignRole('responder');
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $ticket = $this->makeOnProgressTicket($pic, $other, ['sub_status' => Ticket::SUB_STATUS_RESPONSE]);
        $this->addAnalysis($ticket, $pic);

        $this->actingAs($responder);
        $this->expectException(\Illuminate\Auth\Access\AuthorizationException::class);

        (new IncidentResponseService)->storeAction(
            $ticket,
            $responder,
            IncidentResponseAction::TYPE_MITIGATION,
            'Coba tanpa penugasan',
        );
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeOnProgressTicket(User $pic, User $assignee, array $overrides = []): Ticket
    {
        $org = Organization::query()->create(['name' => 'Org R']);
        $cat = \App\Models\IncidentCategory::query()->create(['name' => 'Cat R']);

        $ticket = Ticket::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-R-'.strtoupper(Str::random(5)),
            'title' => 'Ticket respond test',
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
