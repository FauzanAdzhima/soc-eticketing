<?php

namespace Tests\Unit;

use App\Models\IncidentAnalysis;
use App\Models\IncidentIoc;
use App\Models\IncidentIocType;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Services\IncidentAnalysisService;
use Database\Seeders\IncidentIocTypeSeeder;
use Database\Seeders\RoleSeeder;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use Tests\TestCase;

class IncidentAnalysisServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        $this->seed(IncidentIocTypeSeeder::class);
    }

    public function test_store_persists_analysis_and_skips_empty_ioc_values(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeAssignableTicket($pic, $analis);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        $service = new IncidentAnalysisService;
        [$record, $mode] = $service->store($ticket, $analis, [
            'severity' => 'Low',
            'impact' => 'Minimal',
            'root_cause' => 'Unknown',
            'recommendation' => 'Monitor',
            'analysis_result' => null,
        ], [
            ['type_id' => $ipType->id, 'value' => '  10.0.0.1  ', 'description' => null],
            ['type_id' => $ipType->id, 'value' => '', 'description' => 'ignored'],
        ]);
        $this->assertSame('created', $mode);
        $this->assertInstanceOf(IncidentAnalysis::class, $record);

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_ANALYSIS, $ticket->sub_status);

        $this->assertSame(1, IncidentIoc::query()->count());
        $this->assertDatabaseHas('incident_ioc', [
            'value' => '10.0.0.1',
        ]);
    }

    public function test_store_rolls_back_when_ioc_insert_fails(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeAssignableTicket($pic, $analis);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        Event::listen('eloquent.creating: '.IncidentIoc::class, function (): void {
            throw new \RuntimeException('simulated IOC persistence failure');
        });

        $service = new IncidentAnalysisService;

        try {
            $service->store($ticket, $analis, [
                'severity' => 'Low',
                'impact' => 'X',
                'root_cause' => 'Y',
                'recommendation' => 'Z',
            ], [
                ['type_id' => $ipType->id, 'value' => '1.1.1.1'],
            ], false);
            $this->fail('Expected exception was not thrown.');
        } catch (\RuntimeException $e) {
            $this->assertSame('simulated IOC persistence failure', $e->getMessage());
        }

        $this->assertSame(0, IncidentAnalysis::query()->count());
        $this->assertSame(0, IncidentIoc::query()->count());

        $ticket->refresh();
        $this->assertSame(Ticket::SUB_STATUS_TRIAGE, $ticket->sub_status);
    }

    public function test_store_denies_unauthorized_user(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $stranger = User::factory()->create();
        $stranger->assignRole('analis');

        $assignedAnalyst = User::factory()->create();
        $assignedAnalyst->assignRole('analis');

        $ticket = $this->makeAssignableTicket($pic, $assignedAnalyst);

        $service = new IncidentAnalysisService;

        $this->expectException(AuthorizationException::class);
        $service->store($ticket, $stranger, [
            'severity' => 'Low',
            'impact' => 'X',
            'root_cause' => 'Y',
            'recommendation' => 'Z',
        ], [], false);
    }

    public function test_store_updates_latest_row_for_same_analyst_when_not_addendum(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeAssignableTicket($pic, $analis);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();
        $domainType = IncidentIocType::query()->where('ioc_type', 'domain')->firstOrFail();

        $service = new IncidentAnalysisService;

        $service->store($ticket, $analis, [
            'severity' => 'Low',
            'impact' => 'A',
            'root_cause' => 'B',
            'recommendation' => 'C',
        ], [
            ['type_id' => $ipType->id, 'value' => '10.0.0.1'],
        ], false);

        $this->assertSame(1, IncidentAnalysis::query()->count());

        [, $mode] = $service->store($ticket, $analis, [
            'severity' => 'High',
            'impact' => 'A2',
            'root_cause' => 'B2',
            'recommendation' => 'C2',
        ], [
            ['type_id' => $domainType->id, 'value' => 'evil.test'],
        ], false);

        $this->assertSame('updated', $mode);
        $this->assertSame(1, IncidentAnalysis::query()->count());
        $this->assertDatabaseHas('incident_analyses', [
            'ticket_id' => $ticket->id,
            'performed_by' => $analis->id,
            'severity' => 'High',
        ]);
        $this->assertDatabaseMissing('incident_ioc', [
            'value' => '10.0.0.1',
        ]);
        $this->assertDatabaseHas('incident_ioc', [
            'value' => 'evil.test',
        ]);
    }

    public function test_store_addendum_inserts_second_row_for_same_analyst(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeAssignableTicket($pic, $analis);
        $ipType = IncidentIocType::query()->where('ioc_type', 'ip')->firstOrFail();

        $service = new IncidentAnalysisService;

        $service->store($ticket, $analis, [
            'severity' => 'Low',
            'impact' => 'X',
            'root_cause' => 'Y',
            'recommendation' => 'Z',
        ], [
            ['type_id' => $ipType->id, 'value' => '10.0.0.1'],
        ], false);

        [, $mode] = $service->store($ticket, $analis, [
            'severity' => 'Medium',
            'impact' => 'X2',
            'root_cause' => 'Y2',
            'recommendation' => 'Z2',
        ], [
            ['type_id' => $ipType->id, 'value' => '10.0.0.2'],
        ], true);

        $this->assertSame('addendum', $mode);
        $this->assertSame(2, IncidentAnalysis::query()->where('ticket_id', $ticket->id)->count());
    }

    private function makeAssignableTicket(User $pic, User|int $assignee): Ticket
    {
        $assigneeId = $assignee instanceof User ? $assignee->id : $assignee;

        $org = \App\Models\Organization::query()->create(['name' => 'Org Svc']);
        $cat = \App\Models\IncidentCategory::query()->create(['name' => 'Cat Svc']);

        $ticket = Ticket::create([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-SVC-'.strtoupper(Str::random(4)),
            'title' => 'Svc ticket',
            'reporter_name' => 'Tester',
            'reporter_email' => 'svc@example.com',
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
        ]);

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $assigneeId,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        return $ticket;
    }
}
