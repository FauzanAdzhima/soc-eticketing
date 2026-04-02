<?php

namespace Tests\Feature;

use App\Livewire\Pages\Tickets\IndexPage;
use App\Models\IncidentCategory;
use App\Models\IncidentAnalysis;
use App\Models\IncidentIoc;
use App\Models\IncidentIocType;
use App\Models\IncidentResponseAction;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use Laravel\Sanctum\Sanctum;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Livewire\Livewire;
use Tests\TestCase;

class TicketListPageTest extends TestCase
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

    public function test_guest_is_redirected_from_ticket_list(): void
    {
        $this->get(route('tickets.index'))->assertRedirect();
    }

    public function test_user_without_ticket_view_cannot_access_ticket_list(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get(route('tickets.index'))->assertForbidden();
    }

    public function test_pic_sees_all_tickets_in_list(): void
    {
        $picA = User::factory()->create();
        $picA->assignRole('pic');
        $picB = User::factory()->create();
        $picB->assignRole('pic');

        $this->makeTicket([
            'ticket_number' => 'TIC-SCOPE-OWNED',
            'title' => 'Owned by A',
            'created_by' => $picA->id,
        ]);

        $this->makeTicket([
            'ticket_number' => 'TIC-SCOPE-FOREIGN',
            'title' => 'Owned by B only',
            'created_by' => $picB->id,
        ]);

        $assignedToA = $this->makeTicket([
            'ticket_number' => 'TIC-SCOPE-ASSIGNED',
            'title' => 'Assigned to A',
            'created_by' => $picB->id,
        ]);
        TicketAssignment::create([
            'ticket_id' => $assignedToA->id,
            'user_id' => $picA->id,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        $response = $this->actingAs($picA)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('TIC-SCOPE-OWNED', false);
        $response->assertSee('TIC-SCOPE-ASSIGNED', false);
        $response->assertSee('TIC-SCOPE-FOREIGN', false);
    }

    public function test_koordinator_sees_all_tickets(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');

        $u1 = User::factory()->create();
        $u1->assignRole('pic');
        $u2 = User::factory()->create();
        $u2->assignRole('pic');

        $this->makeTicket([
            'ticket_number' => 'TIC-ALL-ONE',
            'created_by' => $u1->id,
        ]);
        $this->makeTicket([
            'ticket_number' => 'TIC-ALL-TWO',
            'created_by' => $u2->id,
        ]);

        $response = $this->actingAs($koordinator)->get(route('tickets.index'));

        $response->assertOk();
        $response->assertSee('TIC-ALL-ONE', false);
        $response->assertSee('TIC-ALL-TWO', false);
    }

    public function test_analis_without_assignment_cannot_open_ticket_show_link(): void
    {
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $other = User::factory()->create();
        $other->assignRole('pic');

        $foreign = $this->makeTicket([
            'created_by' => $other->id,
        ]);

        $this->actingAs($analis)->get(route('tickets.show', $foreign))->assertForbidden();
    }

    public function test_pic_can_view_ticket_they_created(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $ticket = $this->makeTicket([
            'title' => 'Judul unik PIC',
            'created_by' => $pic->id,
        ]);

        $this->actingAs($pic)->get(route('tickets.show', $ticket))
            ->assertRedirect(route('tickets.index', ['ticket' => $ticket->public_id]));
    }

    public function test_pic_can_create_ticket_from_list_modal(): void
    {
        $pic = User::factory()->create(['organization_id' => $this->organization->id]);
        $pic->assignRole('pic');

        Livewire::actingAs($pic)
            ->test(IndexPage::class)
            ->call('openCreateModal')
            ->call('selectCreateTicketCategory', $this->category->id)
            ->set('formTitle', 'Insiden dari modal PIC')
            ->set('formIncidentDescription', 'Deskripsi pengujian')
            ->set('formIncidentTime', now()->format('Y-m-d\TH:i'))
            ->set('isOfficialEmployee', true)
            ->set('formReporterOrganizationId', $this->organization->id)
            ->call('createTicket')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('tickets', [
            'title' => 'Insiden dari modal PIC',
            'created_by' => $pic->id,
            'incident_category_id' => $this->category->id,
            'status' => Ticket::STATUS_AWAITING_VERIFICATION,
            'report_status' => Ticket::REPORT_STATUS_PENDING,
        ]);
    }

    public function test_pic_can_view_ticket_they_are_assigned_to(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');

        $other = User::factory()->create();
        $other->assignRole('pic');

        $ticket = $this->makeTicket([
            'title' => 'Tiket assign ke PIC',
            'created_by' => $other->id,
        ]);
        TicketAssignment::create([
            'ticket_id' => $ticket->id,
            'user_id' => $pic->id,
            'is_active' => true,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
        ]);

        $this->actingAs($pic)->get(route('tickets.show', $ticket))
            ->assertRedirect(route('tickets.index', ['ticket' => $ticket->public_id]));
    }

    public function test_pic_can_verify_then_assign_analis_from_modal(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');

        $ticket = $this->makeTicket([
            'created_by' => $pic->id,
        ]);

        Livewire::actingAs($pic)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->call('verifyTicketReport')
            ->assertHasNoErrors()
            ->set('assignAnalystUserId', $analis->id)
            ->call('assignAnalyst')
            ->assertHasNoErrors();

        $ticket->refresh();
        $this->assertSame(Ticket::REPORT_STATUS_VERIFIED, $ticket->report_status);
        $this->assertTrue($ticket->report_is_valid);
        $this->assertSame(Ticket::STATUS_ON_PROGRESS, $ticket->status);
        $this->assertSame(Ticket::SUB_STATUS_TRIAGE, $ticket->sub_status);
        $this->assertTrue(
            $ticket->assignments()
                ->where('user_id', $analis->id)
                ->where('is_active', true)
                ->where('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY)
                ->exists()
        );
    }

    public function test_second_pic_cannot_assign_after_ticket_is_assigned(): void
    {
        $picA = User::factory()->create();
        $picA->assignRole('pic');
        $picB = User::factory()->create();
        $picB->assignRole('pic');
        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $analis2 = User::factory()->create();
        $analis2->assignRole('analis');

        $ticket = $this->makeTicket([
            'created_by' => $picA->id,
        ]);

        Livewire::actingAs($picA)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->call('verifyTicketReport')
            ->set('assignAnalystUserId', $analis->id)
            ->call('assignAnalyst')
            ->assertHasNoErrors();

        Sanctum::actingAs($picB);
        $this->postJson("/api/tickets/{$ticket->id}/assign", ['user_id' => $analis2->id])
            ->assertForbidden();
    }

    public function test_primary_and_contributor_assignments_can_coexist(): void
    {
        $pic = User::factory()->create();
        $pic->assignRole('pic');
        $analisA = User::factory()->create();
        $analisA->assignRole('analis');
        $analisB = User::factory()->create();
        $analisB->assignRole('analis');

        $ticket = $this->makeTicket([
            'created_by' => $pic->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'status' => Ticket::STATUS_OPEN,
            'report_is_valid' => true,
        ]);

        $ticket->assignTo($analisA->id, $pic);
        $ticket->addContributor($analisB->id, $pic);

        $this->assertSame(2, $ticket->assignments()->where('is_active', true)->count());
        $this->assertTrue(
            $ticket->assignments()
                ->where('is_active', true)
                ->where('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY)
                ->where('user_id', $analisA->id)
                ->exists()
        );
        $this->assertTrue(
            $ticket->assignments()
                ->where('is_active', true)
                ->where('kind', TicketAssignment::KIND_CONTRIBUTOR)
                ->where('user_id', $analisB->id)
                ->exists()
        );
    }

    public function test_koordinator_detail_modal_shows_header_and_accordions_and_hides_reopen_response_button(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicket([
            'title' => 'Judul modal detail uji universal',
            'created_by' => $koordinator->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
        ]);

        $analysis = IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $analis->id,
            'severity' => 'High',
            'impact' => 'Dampak uji',
            'root_cause' => 'Akar uji',
            'recommendation' => 'Rekomendasi uji',
            'analysis_result' => 'Hasil analisis uji',
        ]);

        $iocType = IncidentIocType::query()->create([
            'ioc_type' => 'IP Address',
            'description' => 'IOC type test',
        ]);
        IncidentIoc::query()->create([
            'public_id' => (string) Str::uuid(),
            'analysis_id' => $analysis->id,
            'incident_ioc_type_id' => $iocType->id,
            'value' => '10.10.10.10',
            'description' => 'IOC test value',
        ]);

        IncidentResponseAction::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $responder->id,
            'action_type' => IncidentResponseAction::TYPE_MITIGATION,
            'description' => 'Tindakan mitigasi uji',
            'meta' => [],
        ]);

        Livewire::actingAs($koordinator)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->assertSee('Judul modal detail uji universal')
            ->assertSee('Tutup')
            ->assertSee('Laporan awal tiket')
            ->assertSee('Analisis')
            ->assertSee('Tindakan yang dilakukan')
            ->assertSee('Penugasan ulang')
            ->assertSee('Assign kembali ke analis')
            ->assertSee('Assign kembali ke responder')
            ->assertDontSee('Buka kembali fase respons');
    }

    public function test_reassign_group_button_shows_single_active_form_panel(): void
    {
        $koordinator = User::factory()->create();
        $koordinator->assignRole('koordinator');
        $analis = User::factory()->create();
        $analis->assignRole('analis');
        $responder = User::factory()->create();
        $responder->assignRole('responder');

        $ticket = $this->makeTicket([
            'created_by' => $koordinator->id,
            'report_status' => Ticket::REPORT_STATUS_VERIFIED,
            'report_is_valid' => true,
            'status' => Ticket::STATUS_ON_PROGRESS,
            'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
        ]);

        IncidentAnalysis::query()->create([
            'ticket_id' => $ticket->id,
            'performed_by' => $analis->id,
            'severity' => 'Medium',
            'impact' => 'Dampak',
            'root_cause' => 'Akar',
            'recommendation' => 'Rekomendasi',
            'analysis_result' => 'Hasil',
        ]);

        $component = Livewire::actingAs($koordinator)
            ->test(IndexPage::class)
            ->call('openTicketDetail', $ticket->public_id)
            ->assertDontSee('Pilih analis…')
            ->assertDontSee('Pilih responder…');

        $component
            ->call('showReassignAnalyst')
            ->assertSee('Pilih analis…')
            ->assertDontSee('Pilih responder…');

        $component
            ->call('showReassignResponder')
            ->assertSee('Pilih responder…')
            ->assertDontSee('Pilih analis…');
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
}
