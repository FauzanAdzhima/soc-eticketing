<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.layout-main')]
class IndexPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    public bool $isOfficialEmployee = false;

    public string $formTitle = '';

    public string $formReporterName = '';

    public string $formReporterEmail = '';

    public string $formReporterPhone = '';

    public ?int $formReporterOrganizationId = null;

    public string $formReporterOrganizationName = '';

    public ?int $formIncidentCategoryId = null;

    public string $formIncidentSeverity = 'Low';

    public string $formIncidentTime = '';

    public string $formIncidentDescription = '';

    /** @var array<int, mixed> */
    public array $evidenceFiles = [];

    /** Setelah true, form lengkap buat tiket ditampilkan (kategori tetap bisa diubah lewat dropdown). */
    public bool $createTicketShowFullForm = false;

    public ?string $detailTicketPublicId = null;

    /** Dipilih dari select HTML (string id user); di-cast ke int saat assign */
    public ?string $assignAnalystUserId = null;

    public string $rejectReportReason = '';

    public bool $showRejectReportPanel = false;

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);

        $fromQuery = request()->query('ticket');
        if (is_string($fromQuery) && $fromQuery !== '') {
            $ticket = Ticket::query()
                ->where('public_id', $fromQuery)
                ->with(['assignments' => fn ($q) => $q->where('is_active', true)])
                ->first();
            if ($ticket !== null) {
                $this->authorize('view', $ticket);
                $this->detailTicketPublicId = $ticket->public_id;
                $this->syncAssignAnalystUserIdFromTicket($ticket);
                $this->modal('ticket-detail-modal')->show();
            }
        }
    }

    public function openCreateModal(): void
    {
        abort_unless(auth()->user()?->can('ticket.create.pic'), 403);

        $this->resetValidation();
        $this->prefillCreateFormFromAuth();
        $this->createTicketShowFullForm = false;
        $this->modal('ticket-create-pic-modal')->show();
    }

    public function selectCreateTicketCategory(int $categoryId): void
    {
        abort_unless(auth()->user()?->can('ticket.create.pic'), 403);

        if (! IncidentCategory::query()->whereKey($categoryId)->exists()) {
            return;
        }

        $this->formIncidentCategoryId = $categoryId;
        $this->createTicketShowFullForm = true;
        $this->resetValidation();
    }

    public function closeCreateModal(): void
    {
        $this->modal('ticket-create-pic-modal')->close();
        $this->resetCreateFormFields();
    }

    public function openTicketDetail(string $publicId): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $publicId)
            ->with(['assignments' => fn ($q) => $q->where('is_active', true)])
            ->firstOrFail();
        $this->authorize('view', $ticket);
        $this->detailTicketPublicId = $publicId;
        $this->syncAssignAnalystUserIdFromTicket($ticket);
        $this->rejectReportReason = '';
        $this->showRejectReportPanel = false;
        $this->resetValidation();
        $this->modal('ticket-detail-modal')->show();
    }

    public function closeTicketDetail(): void
    {
        $this->modal('ticket-detail-modal')->close();
        $this->detailTicketPublicId = null;
        $this->assignAnalystUserId = null;
        $this->rejectReportReason = '';
        $this->showRejectReportPanel = false;
    }

    public function verifyTicketReport(): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->detailTicketPublicId)
            ->firstOrFail();

        $this->authorize('verifyReport', $ticket);

        try {
            $ticket->verifyReport(auth()->user());
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Laporan terverifikasi. Tiket siap ditugaskan ke analis.');
        $this->rejectReportReason = '';
        $this->showRejectReportPanel = false;
        $this->resetValidation();
    }

    public function openRejectReportPanel(): void
    {
        $this->showRejectReportPanel = true;
        $this->resetValidation();
    }

    public function cancelRejectReport(): void
    {
        $this->showRejectReportPanel = false;
        $this->rejectReportReason = '';
        $this->resetValidation();
    }

    public function rejectTicketReport(): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->detailTicketPublicId)
            ->firstOrFail();

        $this->authorize('rejectReport', $ticket);

        $this->validate([
            'rejectReportReason' => ['required', 'string', 'min:15', 'max:2000'],
        ], [
            'rejectReportReason.required' => 'Berikan alasan penolakan laporan.',
            'rejectReportReason.min' => 'Alasan penolakan minimal 15 karakter.',
        ]);

        try {
            $ticket->rejectReport(auth()->user(), trim($this->rejectReportReason));
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Laporan ditolak. Tiket tidak akan dilanjutkan ke analis.');
        $this->rejectReportReason = '';
        $this->showRejectReportPanel = false;
        $this->resetValidation();
    }

    public function assignAnalyst(): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->detailTicketPublicId)
            ->firstOrFail();

        $this->authorize('assign', $ticket);

        $this->validate([
            'assignAnalystUserId' => ['required', 'integer', 'exists:users,id'],
        ], [
            'assignAnalystUserId.required' => 'Pilih analis terlebih dahulu.',
            'assignAnalystUserId.integer' => 'Pilihan analis tidak valid.',
        ]);

        $target = User::query()->findOrFail((int) $this->assignAnalystUserId);

        if (! $target->hasRole('analis')) {
            $this->addError('assignAnalystUserId', 'Hanya pengguna dengan peran analis yang dapat ditugaskan di sini.');

            return;
        }

        $already = $ticket->assignments()
            ->where('user_id', $target->id)
            ->where('is_active', true)
            ->exists();

        if ($already) {
            $this->addError('assignAnalystUserId', 'Pengguna ini sudah ditugaskan pada tiket ini.');

            return;
        }

        $ticket->assignTo($target->id, auth()->user());

        session()->flash('toast_success', 'Tiket berhasil ditugaskan ke '.$target->name.'.');
        $this->assignAnalystUserId = null;
        $this->resetValidation();
    }

    public function createTicket(TicketService $ticketService): void
    {
        abort_unless(auth()->user()?->can('ticket.create.pic'), 403);

        $this->validate($this->createTicketRules());

        $hasOrgId = filled($this->formReporterOrganizationId);
        $hasOrgName = filled($this->formReporterOrganizationName);
        if ($hasOrgId === $hasOrgName) {
            $this->addError('formReporterOrganizationId', 'Pilih salah satu jenis organisasi pelapor.');

            return;
        }

        $ticket = $ticketService->createTicket([
            'title' => $this->formTitle,
            'reporter_name' => $this->formReporterName,
            'reporter_email' => $this->formReporterEmail,
            'reporter_phone' => $this->formReporterPhone !== '' ? $this->formReporterPhone : null,
            'reporter_organization_id' => $hasOrgId ? $this->formReporterOrganizationId : null,
            'reporter_organization_name' => $hasOrgName ? $this->formReporterOrganizationName : null,
            'incident_category_id' => $this->formIncidentCategoryId,
            'incident_severity' => $this->formIncidentSeverity,
            'incident_description' => $this->formIncidentDescription,
            'incident_time' => $this->formIncidentTime,
            'evidence_files' => $this->evidenceFiles,
            'created_by' => auth()->id(),
        ]);

        session()->flash('toast_success', 'Tiket berhasil dibuat: '.$ticket->ticket_number);
        $this->modal('ticket-create-pic-modal')->close();
        $this->resetCreateFormFields();
        $this->resetPage();
    }

    public function isImageFile(mixed $file): bool
    {
        try {
            if (! is_object($file) || ! method_exists($file, 'getMimeType')) {
                return false;
            }

            return str_starts_with((string) $file->getMimeType(), 'image/');
        } catch (\Throwable) {
            return false;
        }
    }

    public function evidenceOriginalName(mixed $file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                return (string) $file->getClientOriginalName();
            }
        } catch (\Throwable) {
            // Ignore invalid temporary upload state.
        }

        return 'Lampiran';
    }

    public function evidenceSizeKb(mixed $file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getSize')) {
                $size = (int) ($file->getSize() ?? 0);

                return number_format($size / 1024, 1);
            }
        } catch (\Throwable) {
            // Ignore invalid temporary upload state.
        }

        return '0.0';
    }

    /**
     * @return Builder<Ticket>
     */
    private function ticketsQuery(): Builder
    {
        $user = auth()->user();

        return Ticket::query()
            ->with([
                'category',
                'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
            ])
            ->when(! $user->can('ticket.view_all') && ! $user->hasRole('pic'), function (Builder $q) use ($user): void {
                $q->where(function (Builder $q) use ($user): void {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('assignments', function (Builder $q) use ($user): void {
                            $q->where('user_id', $user->id)->where('is_active', true);
                        });
                });
            })
            ->latest('created_at');
    }

    private function syncAssignAnalystUserIdFromTicket(Ticket $ticket): void
    {
        $ticket->loadMissing(['assignments' => fn ($q) => $q->where('is_active', true)]);
        $primary = $ticket->assignments
            ->firstWhere('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY);
        $this->assignAnalystUserId = $primary !== null ? (string) $primary->user_id : null;
    }

    private function prefillCreateFormFromAuth(): void
    {
        $user = auth()->user();
        assert($user !== null);

        $this->formReporterName = $user->name;
        $this->formReporterEmail = $user->email;
        $this->formReporterPhone = '';
        $this->formTitle = '';
        $this->formIncidentCategoryId = null;
        $this->formIncidentSeverity = 'Low';
        $this->formIncidentTime = now()->format('Y-m-d\TH:i');
        $this->formIncidentDescription = '';
        $this->evidenceFiles = [];

        if ($user->organization_id) {
            $this->isOfficialEmployee = true;
            $this->formReporterOrganizationId = $user->organization_id;
            $this->formReporterOrganizationName = '';
        } else {
            $this->isOfficialEmployee = false;
            $this->formReporterOrganizationId = null;
            $this->formReporterOrganizationName = '';
        }
    }

    private function resetCreateFormFields(): void
    {
        $this->isOfficialEmployee = false;
        $this->formTitle = '';
        $this->formReporterName = '';
        $this->formReporterEmail = '';
        $this->formReporterPhone = '';
        $this->formReporterOrganizationId = null;
        $this->formReporterOrganizationName = '';
        $this->formIncidentCategoryId = null;
        $this->formIncidentSeverity = 'Low';
        $this->formIncidentTime = now()->format('Y-m-d\TH:i');
        $this->formIncidentDescription = '';
        $this->evidenceFiles = [];
        $this->createTicketShowFullForm = false;
    }

    /**
     * @return array<string, mixed>
     */
    private function createTicketRules(): array
    {
        return [
            'formTitle' => ['required', 'string', 'max:255'],
            'formReporterName' => ['required', 'string', 'max:255'],
            'formReporterEmail' => ['required', 'email', 'max:255'],
            'formReporterPhone' => ['nullable', 'string', 'max:30'],
            'formReporterOrganizationId' => ['nullable', 'exists:organizations,id'],
            'formReporterOrganizationName' => ['nullable', 'string', 'max:255'],
            'formIncidentCategoryId' => ['required', 'exists:incident_categories,id'],
            'formIncidentSeverity' => ['required', 'in:Low,Medium,High,Critical'],
            'formIncidentTime' => ['required', 'date'],
            'formIncidentDescription' => ['required', 'string'],
            'evidenceFiles' => ['nullable', 'array'],
            'evidenceFiles.*' => ['file', 'max:5120', 'mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar'],
        ];
    }

    public function render(): View
    {
        $tickets = $this->ticketsQuery()->paginate(15);

        $detailTicket = null;
        if ($this->detailTicketPublicId !== null && $this->detailTicketPublicId !== '') {
            $detailTicket = Ticket::query()
                ->where('public_id', $this->detailTicketPublicId)
                ->with([
                    'category',
                    'creator',
                    'evidences',
                    'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
                ])
                ->first();
        }

        return view('livewire.pages.tickets.index-page', [
            'tickets' => $tickets,
            'categories' => IncidentCategory::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'detailTicket' => $detailTicket,
            'analysts' => User::role('analis')->orderBy('name')->get(),
        ]);
    }
}
