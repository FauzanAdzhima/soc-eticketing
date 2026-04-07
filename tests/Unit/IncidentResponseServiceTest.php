<?php

namespace Tests\Unit;

use App\Models\IncidentAnalysis;
use App\Models\IncidentResponseAction;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\User;
use App\Services\IncidentResponseService;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Tests\TestCase;

class IncidentResponseServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_store_action_inserts_row_and_ticket_log(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicketReadyForResponse($pic, $responder);

        $service = new IncidentResponseService;
        $action = $service->storeAction(
            $ticket,
            $responder,
            IncidentResponseAction::TYPE_MITIGATION,
            '  Isolasi host terpengaruh  ',
            ['host' => 'srv-01'],
        );

        $this->assertInstanceOf(IncidentResponseAction::class, $action);
        $this->assertSame(IncidentResponseAction::TYPE_MITIGATION, $action->action_type);
        $this->assertSame(['host' => 'srv-01'], $action->meta);

        $this->assertTrue(
            TicketLog::query()
                ->where('ticket_id', $ticket->id)
                ->where('user_id', $responder->id)
                ->where('action', 'response_action_recorded')
                ->exists()
        );
    }

    public function test_store_action_denies_analyst_without_respond_permission(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeTicketReadyForResponse($pic, $analis);

        $service = new IncidentResponseService;

        $this->expectException(AuthorizationException::class);
        $service->storeAction(
            $ticket,
            $analis,
            IncidentResponseAction::TYPE_MITIGATION,
            'Test',
        );
    }

    public function test_store_action_rejects_invalid_action_type(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicketReadyForResponse($pic, $responder);
        $service = new IncidentResponseService;

        $this->expectException(InvalidArgumentException::class);
        $service->storeAction($ticket, $responder, 'invalid_type', 'X');
    }

    public function test_store_action_denies_when_sub_status_is_analysis(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicketReadyForResponse($pic, $responder, subStatus: Ticket::SUB_STATUS_ANALYSIS);
        $service = new IncidentResponseService;

        $this->expectException(AuthorizationException::class);
        $service->storeAction(
            $ticket,
            $responder,
            IncidentResponseAction::TYPE_MITIGATION,
            'Terlalu awal',
        );
    }

    public function test_store_action_denies_when_sub_status_is_resolution(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicketReadyForResponse($pic, $responder, subStatus: Ticket::SUB_STATUS_RESOLUTION);
        $service = new IncidentResponseService;

        $this->expectException(AuthorizationException::class);
        $service->storeAction(
            $ticket,
            $responder,
            IncidentResponseAction::TYPE_MITIGATION,
            'Setelah Resolution',
        );
    }

    public function test_mark_response_resolved_promotes_sub_status(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicketReadyForResponse($pic, $responder);
        $service = new IncidentResponseService;
        $service->storeAction($ticket, $responder, IncidentResponseAction::TYPE_MITIGATION, 'Langkah pertama');

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $ticket->sub_status);

        $service->markResponseResolved($ticket->fresh(), $responder);

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_RESOLUTION, $ticket->sub_status);
        $this->assertTrue($ticket->isClosed());
    }

    private function makeTicketReadyForResponse(User $pic, User $assignee, string $subStatus = Ticket::SUB_STATUS_RESPONSE): Ticket
    {
        $org = Organization::query()->create(['name' => 'Org Resp']);
        $cat = \App\Models\IncidentCategory::query()->create(['name' => 'Cat Resp']);

        $ticket = Ticket::create([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-R-'.strtoupper(Str::random(4)),
            'title' => 'Resp ticket',
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
            'sub_status' => $subStatus,
            'created_by' => $pic->id,
        ]);

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $assignee->id,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $assignee->id,
            'severity' => 'Medium',
            'impact' => 'i',
            'root_cause' => 'r',
            'recommendation' => 'rec',
        ]);

        return $ticket->fresh();
    }
}
