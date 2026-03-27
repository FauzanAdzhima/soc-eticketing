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
        $ticket = $this->makeTicket();
        $targetUser = User::factory()->create();
        $targetUser->assignRole('analis');

        $pic = User::factory()->create();
        $pic->assignRole('pic');
        Sanctum::actingAs($pic);

        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $targetUser->id])
            ->assertOk();
    }

    public function test_responder_can_update_status_when_assigned(): void
    {
        $ticket = $this->makeTicket(['status' => 'analyzed']);
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $responder->id,
        ]);

        Sanctum::actingAs($responder);

        $this->patchJson("/api/tickets/{$ticket->id}/status", ['status' => 'responded'])
            ->assertOk();
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
            'status' => 'open',
        ], $overrides));
    }
}
