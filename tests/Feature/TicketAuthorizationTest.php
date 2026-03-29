<?php

namespace Tests\Feature;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TicketAuthorizationTest extends TestCase
{
    use RefreshDatabase;

    protected Organization $organization;
    protected IncidentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->organization = Organization::create(['name' => 'Diskominfo']);
        $this->category = IncidentCategory::create(['name' => 'Phishing']);
    }

    public function test_guest_can_create_ticket_via_public_endpoint(): void
    {
        $response = $this->postJson('/api/tickets/public', $this->ticketPayload());

        $response->assertCreated();
        $this->assertDatabaseCount('tickets', 1);
    }

    public function test_pic_can_create_ticket_via_authenticated_endpoint(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $response = $this->postJson('/api/tickets', $this->ticketPayload());

        $response->assertCreated();
    }

    public function test_non_pic_cannot_create_ticket_via_authenticated_endpoint(): void
    {
        $user = User::factory()->create();
        $user->assignRole('analis');
        Sanctum::actingAs($user);

        $response = $this->postJson('/api/tickets', $this->ticketPayload());

        $response->assertForbidden();
    }

    public function test_assignment_endpoint_requires_assign_permission(): void
    {
        $ticket = $this->makeTicket();
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $responder = User::factory()->create();
        $responder->assignRole('responder');
        Sanctum::actingAs($responder);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $targetUser->id])
            ->assertForbidden();
    }

    public function test_pic_can_assign_ticket(): void
    {
        $ticket = $this->makeTicket([
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'status' => Ticket::STATUS_OPEN,
            'report_is_valid' => true,
        ]);
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $response = $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $targetUser->id]);
        $response->assertOk();
        $response->assertJsonPath('data.report_status', Ticket::REPORT_STATUS_VERIFIED);
        $response->assertJsonPath('data.status', Ticket::STATUS_ON_PROGRESS);
        $response->assertJsonPath('data.sub_status', Ticket::SUB_STATUS_TRIAGE);

        $ticket->refresh();
        $this->assertSame(Ticket::REPORT_STATUS_VERIFIED, $ticket->report_status);
        $this->assertSame(Ticket::STATUS_ON_PROGRESS, $ticket->status);
    }

    public function test_pic_cannot_assign_before_report_is_verified(): void
    {
        $ticket = $this->makeTicket();
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $targetUser->id])
            ->assertForbidden();
    }

    public function test_koordinator_can_assign_when_ticket_already_on_progress(): void
    {
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $ticket = $this->makeTicket([
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_TRIAGE,
            'report_is_valid' => true,
        ]);

        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        Sanctum::actingAs($koordinator);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $targetUser->id])
            ->assertOk();
    }

    public function test_assign_accepts_kind_contributor_without_replacing_primary(): void
    {
        $ticket = $this->makeTicket([
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'status' => Ticket::STATUS_OPEN,
            'report_is_valid' => true,
        ]);
        $primary = User::factory()->create();
        $primary->assignRole('analis');
        $contributor = User::factory()->create();
        $contributor->assignRole('analis');

        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        Sanctum::actingAs($koordinator);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $primary->id])
            ->assertOk();

        $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'user_id' => $contributor->id,
            'kind' => TicketAssignment::KIND_CONTRIBUTOR,
        ])->assertOk();

        $this->assertSame(2, $ticket->fresh()->assignments()->where('is_active', true)->count());
    }

    public function test_assign_rejects_invalid_kind(): void
    {
        $ticket = $this->makeTicket([
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'status' => Ticket::STATUS_OPEN,
            'report_is_valid' => true,
        ]);
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $this->postJson("/api/tickets/{$ticket->id}/assign", [
            'user_id' => $targetUser->id,
            'kind' => 'invalid_kind',
        ])->assertUnprocessable();
    }

    public function test_responder_can_update_status_when_assigned(): void
    {
        $ticket = $this->makeTicket([
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_TRIAGE,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
        ]);
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $responder->id,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        Sanctum::actingAs($responder);

        $this->patchJson("/api/tickets/{$ticket->id}/status", ['sub_status' => Ticket::SUB_STATUS_RESPONSE])
            ->assertOk();

        $this->assertSame(Ticket::SUB_STATUS_RESPONSE, $ticket->fresh()->sub_status);
    }

    public function test_pic_can_verify_report_via_api(): void
    {
        $ticket = $this->makeTicket();
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $this->postJson("/api/tickets/{$ticket->id}/verify")->assertOk();

        $ticket->refresh();
        $this->assertSame(Ticket::REPORT_STATUS_VERIFIED, $ticket->report_status);
        $this->assertTrue($ticket->report_is_valid);
        $this->assertSame(Ticket::STATUS_OPEN, $ticket->status);
    }

    public function test_koordinator_can_close_ticket_via_api(): void
    {
        $ticket = $this->makeTicket([
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
        ]);

        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        Sanctum::actingAs($koordinator);

        $this->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_CLOSED])
            ->assertOk();

        $this->assertTrue($ticket->fresh()->isClosed());
    }

    public function test_pic_can_reject_report_via_api(): void
    {
        $ticket = $this->makeTicket();
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $reason = 'Laporan tidak valid dan tidak ada bukti mendukung.';
        $this->postJson("/api/tickets/{$ticket->id}/reject", ['reason' => $reason])->assertOk();

        $ticket->refresh();
        $this->assertSame(Ticket::REPORT_STATUS_REJECTED, $ticket->report_status);
        $this->assertFalse($ticket->report_is_valid);
        $this->assertSame(Ticket::STATUS_REPORT_REJECTED, $ticket->status);
        $this->assertSame($reason, $ticket->report_rejection_reason);
        $this->assertTrue($ticket->isReportRejected());
    }

    public function test_koordinator_cannot_assign_after_report_rejected(): void
    {
        $ticket = $this->makeTicket();
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);
        $this->postJson("/api/tickets/{$ticket->id}/reject", [
            'reason' => 'False report — tidak sesuai fakta lapangan.',
        ])->assertOk();

        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        Sanctum::actingAs($koordinator);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $analis->id])
            ->assertForbidden();
    }

    public function test_koordinator_cannot_close_report_rejected_ticket_via_api(): void
    {
        $ticket = $this->makeTicket();
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);
        $this->postJson("/api/tickets/{$ticket->id}/reject", [
            'reason' => 'Tiket ditolak PIC — tidak memenuhi kriteria.',
        ])->assertOk();

        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        Sanctum::actingAs($koordinator);

        $this->patchJson("/api/tickets/{$ticket->id}/status", ['status' => Ticket::STATUS_CLOSED])
            ->assertForbidden();
    }

    private function ticketPayload(): array
    {
        return [
            'title' => 'Insiden phishing',
            'reporter_name' => 'Pelapor',
            'reporter_email' => 'pelapor@example.com',
            'reporter_organization_name' => 'Instansi A',
            'incident_category_id' => $this->category->id,
            'incident_severity' => 'High',
            'incident_description' => 'Email phishing diterima user',
            'incident_time' => now()->toDateTimeString(),
        ];
    }

    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-' . now()->format('ym') . '-' . strtoupper(Str::random(4)),
            'title' => 'Ticket test',
            'reporter_name' => 'Tester',
            'reporter_email' => 'tester@example.com',
            'reporter_organization_id' => $this->organization->id,
            'reported_at' => now(),
            'incident_time' => now(),
            'incident_severity' => 'Low',
            'incident_description' => 'desc',
            'incident_category_id' => $this->category->id,
            'report_status' => Ticket::REPORT_STATUS_PENDING,
            'report_is_valid' => false,
            'status' => Ticket::STATUS_AWAITING_VERIFICATION,
            'sub_status' => null,
        ], $overrides));
    }
}
