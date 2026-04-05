<?php

namespace Tests\Feature;

use App\Livewire\Ticket\Chat;
use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketChatTest extends TestCase
{
    use RefreshDatabase;

    private Organization $organization;

    private IncidentCategory $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RoleSeeder::class);
        $this->organization = Organization::create(['name' => 'Diskominfo']);
        $this->category = IncidentCategory::create(['name' => 'Email Phishing']);
    }

    public function test_guest_track_chat_returns_403_for_invalid_token(): void
    {
        $ticket = $this->makeTicketWithGuestToken('correct-secret-token');

        $this->get(route('tickets.track.chat', [
            'ticket' => $ticket->public_id,
            'token' => 'wrong-token',
        ]))->assertForbidden();
    }

    public function test_guest_track_chat_returns_403_when_ticket_has_no_token_hash(): void
    {
        $ticket = $this->makeTicket([
            'reporter_chat_token_hash' => null,
            'reporter_chat_token_created_at' => null,
        ]);

        $this->get(route('tickets.track.chat', [
            'ticket' => $ticket->public_id,
            'token' => 'any-plain-token',
        ]))->assertForbidden();
    }

    public function test_guest_track_chat_ok_with_valid_token(): void
    {
        $plain = 'guest-track-ok-token';
        $ticket = $this->makeTicketWithGuestToken($plain);

        $this->get(route('tickets.track.chat', [
            'ticket' => $ticket->public_id,
            'token' => $plain,
        ]))->assertOk();
    }

    public function test_guest_chat_does_not_show_internal_messages(): void
    {
        $plain = 'guest-visible-only-external';
        $ticket = $this->makeTicketWithGuestToken($plain);

        $pic = User::factory()->create();
        $pic->assignRole('pic');

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $pic->id,
            'guest_name' => null,
            'visibility' => TicketMessage::VISIBILITY_INTERNAL,
            'message' => 'INTERNAL_MSG_UNIQUE_XQ9',
        ]);

        TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => $pic->id,
            'guest_name' => null,
            'visibility' => TicketMessage::VISIBILITY_EXTERNAL,
            'message' => 'EXTERNAL_MSG_UNIQUE_YR2',
        ]);

        Livewire::test(Chat::class, [
            'ticket' => $ticket,
            'guestToken' => $plain,
        ])
            ->assertSee('EXTERNAL_MSG_UNIQUE_YR2', false)
            ->assertDontSee('INTERNAL_MSG_UNIQUE_XQ9', false);
    }

    public function test_guest_cannot_download_internal_message_attachment(): void
    {
        $plain = 'guest-attach-token';
        $ticket = $this->makeTicketWithGuestToken($plain);

        $internal = TicketMessage::create([
            'ticket_id' => $ticket->id,
            'user_id' => null,
            'guest_name' => null,
            'visibility' => TicketMessage::VISIBILITY_INTERNAL,
            'message' => 'internal with file',
            'attachment_path' => 'ticket-chat/'.$ticket->public_id.'/fake.pdf',
        ]);

        $this->get(route('tickets.track.chat.attachment', [
            'ticket' => $ticket->public_id,
            'token' => $plain,
            'message' => $internal->id,
        ]))->assertForbidden();
    }

    public function test_staff_without_send_internal_cannot_send_internal_messages(): void
    {
        $role = Role::firstOrCreate(
            ['name' => 'chat-external-only-test', 'guard_name' => 'web'],
        );
        $role->syncPermissions([
            'ticket.view',
            'ticket.view_all',
            'ticket.chat.view',
            'ticket.chat.send_external',
        ]);

        $staff = User::factory()->create();
        $staff->assignRole($role);

        $ticket = $this->makeTicket();

        Livewire::actingAs($staff)
            ->test(Chat::class, ['ticket' => $ticket])
            ->set('visibility', TicketMessage::VISIBILITY_INTERNAL)
            ->assertSet('visibility', TicketMessage::VISIBILITY_EXTERNAL)
            ->set('body', 'Hanya jalur pelapor')
            ->call('sendMessage')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('ticket_messages', [
            'ticket_id' => $ticket->id,
            'visibility' => TicketMessage::VISIBILITY_EXTERNAL,
            'message' => 'Hanya jalur pelapor',
        ]);

        $this->assertSame(
            0,
            TicketMessage::query()
                ->where('ticket_id', $ticket->id)
                ->where('visibility', TicketMessage::VISIBILITY_INTERNAL)
                ->count()
        );
    }

    public function test_send_message_rejects_whitespace_only_body(): void
    {
        $ticket = $this->makeTicket();
        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $before = TicketMessage::query()->where('ticket_id', $ticket->id)->count();

        Livewire::actingAs($pic)
            ->test(Chat::class, ['ticket' => $ticket])
            ->set('body', "  \t  \n  ")
            ->call('sendMessage')
            ->assertHasErrors(['body']);

        $this->assertSame($before, TicketMessage::query()->where('ticket_id', $ticket->id)->count());
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function makeTicket(array $overrides = []): Ticket
    {
        return Ticket::create(array_merge([
            'public_id' => (string) Str::uuid(),
            'ticket_number' => 'TIC-'.now()->format('ym').'-'.strtoupper(Str::random(4)),
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

    private function makeTicketWithGuestToken(string $plainToken): Ticket
    {
        return $this->makeTicket([
            'reporter_chat_token_hash' => hash('sha256', $plainToken),
            'reporter_chat_token_created_at' => now(),
        ]);
    }
}
