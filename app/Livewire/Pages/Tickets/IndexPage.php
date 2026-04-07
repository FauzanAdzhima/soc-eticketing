<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\TicketLog;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Eloquent\Builder;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithFileUploads;
use Livewire\WithPagination;

#[Layout('layouts.layout-main')]
class IndexPage extends Component
{
    use WithFileUploads;
    use WithPagination;

    #[Url(as: 'scope')]
    public ?string $scope = null;

    /** @var 'all'|'ready_for_response'|'in_progress'|'resolved' */
    #[Url(as: 'rf')]
    public string $responderFilterStatus = 'all';

    /** @var 'all'|'Low'|'Medium'|'High'|'Critical' */
    #[Url(as: 'rsev')]
    public string $responderFilterSeverity = 'all';

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

    public ?string $assignResponderUserId = null;

    public string $rejectReportReason = '';

    /** Alasan reopen saat tiket sudah Closed (untuk koordinator). */
    public string $reopenReason = '';

    public bool $showRejectReportPanel = false;

    /** Baseline jumlah tiket dengan penugasan aktif ke user (mode analis), untuk deteksi penugasan baru saat polling. */
    public ?int $analystAssignmentPollBaseline = null;

    public bool $showNewAssignmentBanner = false;

    public ?int $responderAssignmentPollBaseline = null;

    public bool $showResponderNewAssignmentBanner = false;

    /** @var ''|'analyst'|'responder' */
    public string $reassignMode = '';

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
                $this->syncAssignResponderUserIdFromTicket($ticket);
                $this->modal('ticket-detail-modal')->show();
            }
        }
    }

    public function keepAlive(): void {}

    /**
     * Dipanggil dari wire:poll di mode analis: bandingkan jumlah penugasan aktif; jika naik, tampilkan banner.
     */
    public function refreshAssignmentSignal(): void
    {
        if (! $this->isAnalystListMode()) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User || ! $user->can('ticket.analyze')) {
            return;
        }

        $current = Ticket::query()
            ->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->count();

        if ($this->analystAssignmentPollBaseline === null) {
            $this->analystAssignmentPollBaseline = $current;

            return;
        }

        if ($current > $this->analystAssignmentPollBaseline) {
            $this->showNewAssignmentBanner = true;
        }

        $this->analystAssignmentPollBaseline = $current;
    }

    public function dismissNewAssignmentBanner(): void
    {
        $this->showNewAssignmentBanner = false;
    }

    /**
     * Dipanggil dari wire:poll di mode responder: bandingkan jumlah penugasan aktif pada tiket siap penanganan.
     */
    public function refreshResponderAssignmentSignal(): void
    {
        if (! $this->isResponderListMode()) {
            return;
        }

        $user = auth()->user();
        if (! $user instanceof User || ! $user->can('ticket.respond')) {
            return;
        }

        $current = Ticket::query()
            ->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
            ->whereHas('analyses')
            ->whereIn('sub_status', $this->responderQueueSubStatuses())
            ->count();

        if ($this->responderAssignmentPollBaseline === null) {
            $this->responderAssignmentPollBaseline = $current;

            return;
        }

        if ($current > $this->responderAssignmentPollBaseline) {
            $this->showResponderNewAssignmentBanner = true;
        }

        $this->responderAssignmentPollBaseline = $current;
    }

    public function dismissResponderNewAssignmentBanner(): void
    {
        $this->showResponderNewAssignmentBanner = false;
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
            ->first();

        if ($ticket === null) {
            session()->flash('toast_error', 'Tiket tidak ditemukan.');

            return;
        }

        $this->authorize('view', $ticket);
        $this->detailTicketPublicId = $publicId;
        $this->syncAssignAnalystUserIdFromTicket($ticket);
        $this->syncAssignResponderUserIdFromTicket($ticket);
        $this->rejectReportReason = '';
        $this->reopenReason = '';
        $this->showRejectReportPanel = false;
        $this->reassignMode = '';
        $this->resetValidation();
        $this->modal('ticket-detail-modal')->show();
    }

    public function closeTicketDetail(): void
    {
        $this->modal('ticket-detail-modal')->close();
        $this->detailTicketPublicId = null;
        $this->assignAnalystUserId = null;
        $this->assignResponderUserId = null;
        $this->rejectReportReason = '';
        $this->reopenReason = '';
        $this->showRejectReportPanel = false;
        $this->reassignMode = '';
    }

    public function showReassignAnalyst(): void
    {
        $this->reassignMode = 'analyst';
        $this->resetValidation();
    }

    public function showReassignResponder(): void
    {
        $this->reassignMode = 'responder';
        $this->resetValidation();
    }

    public function verifyTicketReport(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

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
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

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
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

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
        $this->reassignMode = '';
        $this->resetValidation();
        $fresh = $ticket->fresh();
        if ($fresh !== null) {
            $this->syncAssignAnalystUserIdFromTicket($fresh);
            $this->syncAssignResponderUserIdFromTicket($fresh);
        }
    }

    /**
     * Handoff penugasan utama ke pengguna dengan izin ticket.respond (koordinator / PIC sesuai kebijakan assign).
     */
    public function assignHandoffResponder(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

        $this->authorize('assignResponderHandoff', $ticket);

        $this->validate([
            'assignResponderUserId' => ['required', 'integer', 'exists:users,id'],
        ], [
            'assignResponderUserId.required' => 'Pilih responder terlebih dahulu.',
        ]);

        $target = User::query()->findOrFail((int) $this->assignResponderUserId);

        if (! $target->can('ticket.respond')) {
            $this->addError('assignResponderUserId', 'Hanya pengguna dengan izin penanganan respons yang dapat dipilih.');

            return;
        }

        $already = $ticket->assignments()
            ->where('user_id', $target->id)
            ->where('is_active', true)
            ->exists();

        if ($already) {
            $this->addError('assignResponderUserId', 'Pengguna ini sudah ditugaskan pada tiket ini.');

            return;
        }

        $ticket->loadMissing(['assignments' => fn ($q) => $q->where('is_active', true)]);
        $formerPrimary = $ticket->assignments
            ->firstWhere('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY);
        $formerPrimaryId = $formerPrimary?->user_id;

        $ticket->assignTo($target->id, auth()->user());

        if (
            $formerPrimaryId !== null
            && $formerPrimaryId !== $target->id
        ) {
            $formerUser = User::query()->find($formerPrimaryId);
            if ($formerUser !== null && $formerUser->can('ticket.analyze')) {
                $hasActiveForFormer = $ticket->assignments()
                    ->where('user_id', $formerPrimaryId)
                    ->where('is_active', true)
                    ->exists();
                if (! $hasActiveForFormer) {
                    $ticket->addContributor($formerPrimaryId, auth()->user());
                }
            }
        }

        session()->flash('toast_success', 'Tiket ditugaskan ke responder '.$target->name.'.');
        $this->assignResponderUserId = null;
        $this->reassignMode = '';
        $this->syncAssignResponderUserIdFromTicket($ticket->fresh());
        $this->resetValidation();
    }

    /**
     * PIC / Koordinator: batalkan penugasan utama terakhir (dalam batas waktu).
     */
    public function cancelAssignment(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

        $this->authorize('cancelAssignment', $ticket);

        $user = auth()->user();
        assert($user instanceof User);

        try {
            $ticket->cancelLatestAssignment($user);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Penugasan berhasil dibatalkan. Tiket siap ditugaskan kembali.');
        $this->assignAnalystUserId = null;
        $this->assignResponderUserId = null;
        $this->reassignMode = '';
        $this->resetValidation();
        $fresh = $ticket->fresh();
        if ($fresh !== null) {
            $this->syncAssignAnalystUserIdFromTicket($fresh);
            $this->syncAssignResponderUserIdFromTicket($fresh);
        }
    }

    /**
     * Koordinator: buka kembali fase Response dari Resolution agar responder dapat menambah catatan tindakan.
     */
    public function reopenResponseRecording(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

        $this->authorize('reopenResponseRecording', $ticket);

        $user = auth()->user();
        assert($user instanceof User);

        try {
            $ticket->reopenResponsePhaseForAdditionalActions($user);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Fase respons dibuka kembali. Responder dapat menambah catatan tindakan lewat halaman penanganan.');
    }

    /**
     * Penutupan manual oleh koordinator (alur utama: responder menutup saat menandai selesai penanganan).
     */
    public function closeTicketByCoordinator(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

        $this->authorize('close', $ticket);

        $user = auth()->user();
        assert($user instanceof User);

        try {
            $ticket->close($user);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        session()->flash('toast_success', 'Tiket ditutup untuk alur koordinator.');
        $this->closeTicketDetail();
    }

    /**
     * Koordinator: membuka kembali tiket Closed untuk fase Response.
     */
    public function reopenClosedByCoordinator(): void
    {
        $ticket = $this->resolveDetailTicketOrFlash();
        if ($ticket === null) {
            return;
        }

        $this->authorize('reopenClosed', $ticket);

        $this->validate([
            'reopenReason' => ['required', 'string', 'min:15', 'max:2000'],
        ], [
            'reopenReason.required' => 'Berikan alasan reopen.',
            'reopenReason.min' => 'Alasan reopen minimal 15 karakter.',
        ]);

        $user = auth()->user();
        assert($user instanceof User);

        try {
            $ticket->reopenClosed($user, trim($this->reopenReason));
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        $this->reopenReason = '';
        $this->resetValidation();

        session()->flash('toast_success', 'Tiket dibuka kembali untuk fase Response.');
    }

    public function createTicket(TicketService $ticketService): void
    {
        abort_unless(auth()->user()?->can('ticket.create.pic'), 403);

        $this->validate($this->createTicketRules(), [
            'formReporterName.regex' => 'Nama hanya boleh berisi huruf, spasi, titik, dan tanda hubung.',
            'formReporterPhone.regex' => 'Nomor telepon harus diawali 08, 62, atau +62.',
            'formReporterPhone.max' => 'Nomor telepon maksimal 15 karakter.',
        ]);

        $hasOrgId = filled($this->formReporterOrganizationId);
        $hasOrgName = filled($this->formReporterOrganizationName);
        if ($hasOrgId === $hasOrgName) {
            $this->addError('formReporterOrganizationId', 'Pilih salah satu jenis organisasi pelapor.');

            return;
        }

        $result = $ticketService->createTicket([
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

        session()->flash('toast_success', 'Tiket berhasil dibuat: '.$result->ticket->ticket_number);
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

    private function isAnalystListMode(): bool
    {
        if ($this->scope === 'analyst') {
            return true;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->seesOnlyAnalystTicketListInNavigation();
    }

    private function isResponderListMode(): bool
    {
        if ($this->scope === 'responder') {
            return true;
        }

        $user = auth()->user();
        if (! $user instanceof User) {
            return false;
        }

        return $user->seesOnlyResponderTicketListInNavigation();
    }

    /**
     * @return Builder<Ticket>
     */
    private function ticketsQuery(): Builder
    {
        $user = auth()->user();
        assert($user instanceof User);

        $q = Ticket::query()
            ->with([
                'category',
                'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
            ]);

        if ($this->isAnalystListMode()) {
            $q->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
                ->withExists('analyses');
        } elseif ($this->isResponderListMode()) {
            $q->whereHas('assignments', function (Builder $q) use ($user): void {
                $q->where('user_id', $user->id)->where('is_active', true);
            })
                ->whereHas('analyses')
                ->whereIn('sub_status', $this->responderQueueSubStatuses())
                ->withCount('responseActions');

            if ($this->responderFilterStatus === 'ready_for_response') {
                $q->where('sub_status', Ticket::SUB_STATUS_RESPONSE)
                    ->whereDoesntHave('responseActions');
            } elseif ($this->responderFilterStatus === 'in_progress') {
                $q->where('sub_status', Ticket::SUB_STATUS_RESPONSE)
                    ->whereHas('responseActions');
            } elseif ($this->responderFilterStatus === 'resolved') {
                $q->where('sub_status', Ticket::SUB_STATUS_RESOLUTION);
            }

            if (
                $this->responderFilterSeverity !== 'all'
                && in_array($this->responderFilterSeverity, ['Low', 'Medium', 'High', 'Critical'], true)
            ) {
                $q->whereRaw(
                    '(select severity from incident_analyses where incident_analyses.ticket_id = tickets.id order by incident_analyses.created_at desc, incident_analyses.id desc limit 1) = ?',
                    [$this->responderFilterSeverity]
                );
            }
        } else {
            $q->when(! $user->can('ticket.view_all') && ! $user->hasRole('pic'), function (Builder $q) use ($user): void {
                $q->where(function (Builder $q) use ($user): void {
                    $q->where('created_by', $user->id)
                        ->orWhereHas('assignments', function (Builder $q) use ($user): void {
                            $q->where('user_id', $user->id)->where('is_active', true);
                        });
                });
            });
        }

        return $q->latest('created_at');
    }

    /**
     * Sub-status tiket yang masuk antrean responder (selaras dengan gate view responder-only dan polling).
     *
     * @return list<string>
     */
    private function responderQueueSubStatuses(): array
    {
        return [
            Ticket::SUB_STATUS_ANALYSIS,
            Ticket::SUB_STATUS_RESPONSE,
            Ticket::SUB_STATUS_RESOLUTION,
        ];
    }

    /**
     * @return \Illuminate\Support\Collection<int, array{label: string, description: string, at: \Illuminate\Support\Carbon|null, color: string, elapsed_text: string|null}>
     */
    private function buildTicketTimeline(Ticket $ticket): \Illuminate\Support\Collection
    {
        $logs = TicketLog::query()
            ->where('ticket_id', $ticket->id)
            ->whereIn('action', [
                'report_verified',
                'report_rejected',
                'assigned',
                'contributor_assigned',
                'sub_status_updated',
                'response_marked_resolved',
                'assignment_cancelled',
                'handling_validated',
                'closed',
                'ticket_reopened',
            ])
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $userIds = collect([$ticket->created_by]);
        foreach ($logs as $log) {
            if ($log->user_id) {
                $userIds->push($log->user_id);
            }
            $data = json_decode((string) $log->data, true) ?: [];
            if (isset($data['assigned_to'])) {
                $userIds->push($data['assigned_to']);
            }
            if (isset($data['cancelled_user_id'])) {
                $userIds->push($data['cancelled_user_id']);
            }
            if (isset($data['restored_user_id'])) {
                $userIds->push($data['restored_user_id']);
            }
        }
        $userNames = User::whereIn('id', $userIds->filter()->unique()->values())
            ->pluck('name', 'id');

        $entries = collect();

        $entries->push([
            'label' => 'Tiket Masuk',
            'description' => 'Tiket dibuat oleh '.($userNames[$ticket->created_by] ?? 'Sistem'),
            'at' => $ticket->created_at,
            'color' => 'sky',
        ]);

        foreach ($logs as $log) {
            $data = json_decode((string) $log->data, true) ?: [];
            $actorName = $log->user_id ? ($userNames[$log->user_id] ?? '—') : 'Sistem';

            $entry = match ($log->action) {
                'report_verified' => [
                    'label' => 'PIC Verifikasi Laporan',
                    'description' => $actorName.' memverifikasi laporan sebagai valid',
                    'color' => 'emerald',
                ],
                'report_rejected' => [
                    'label' => 'PIC Menolak Laporan',
                    'description' => $actorName.' menolak laporan',
                    'color' => 'red',
                ],
                'assigned' => [
                    'label' => 'Penugasan Petugas',
                    'description' => $actorName.' menugaskan tiket ke '.($userNames[$data['assigned_to'] ?? 0] ?? '—'),
                    'color' => 'violet',
                ],
                'contributor_assigned' => [
                    'label' => 'Kontributor Ditambahkan',
                    'description' => $actorName.' menambahkan kontributor '.($userNames[$data['assigned_to'] ?? 0] ?? '—'),
                    'color' => 'violet',
                ],
                'assignment_cancelled' => [
                    'label' => 'Penugasan Dibatalkan',
                    'description' => $actorName.' membatalkan penugasan '.($userNames[$data['cancelled_user_id'] ?? 0] ?? '—')
                        .(! empty($data['restored_user_id']) ? ', penugasan '
                            .($userNames[$data['restored_user_id']] ?? '—')
                            .' dipulihkan' : ''),
                    'color' => 'red',
                ],
                'sub_status_updated' => $this->mapSubStatusTimelineEntry($data, $actorName),
                'response_marked_resolved' => [
                    'label' => 'Penanganan Respons Selesai',
                    'description' => $actorName.' menandai penanganan respons selesai',
                    'color' => 'green',
                ],
                'handling_validated' => [
                    'label' => 'Penanganan Divalidasi',
                    'description' => $actorName.' memvalidasi penanganan insiden',
                    'color' => 'purple',
                ],
                'closed' => [
                    'label' => 'Tiket Ditutup',
                    'description' => 'Tiket ditutup oleh '.$actorName,
                    'color' => 'green',
                ],
                'ticket_reopened' => [
                    'label' => 'Tiket Dibuka Kembali',
                    'description' => $actorName.' membuka kembali tiket',
                    'color' => 'red',
                ],
                default => null,
            };

            if ($entry !== null) {
                $entry['at'] = $log->created_at;
                $entries->push($entry);
            }
        }

        $sorted = $entries->sortBy('at')->values();

        return $sorted->map(function (array $entry, int $index) use ($sorted): array {
            $entry['elapsed_text'] = null;
            if ($index > 0) {
                $prev = $sorted[$index - 1]['at'];
                $current = $entry['at'];
                if ($prev !== null && $current !== null) {
                    $totalMinutes = (int) $prev->diffInMinutes($current, true);
                    $days = intdiv($totalMinutes, 1440);
                    $hours = intdiv($totalMinutes % 1440, 60);
                    $minutes = $totalMinutes % 60;
                    $parts = [];
                    if ($days > 0) {
                        $parts[] = $days.' hari';
                    }
                    if ($hours > 0) {
                        $parts[] = $hours.' jam';
                    }
                    if ($minutes > 0) {
                        $parts[] = $minutes.' menit';
                    }
                    $entry['elapsed_text'] = $parts !== [] ? implode(' ', $parts) : '< 1 menit';
                }
            }

            return $entry;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array{label: string, description: string, color: string}
     */
    private function mapSubStatusTimelineEntry(array $data, string $actorName): array
    {
        $to = (string) ($data['to'] ?? '');
        $from = (string) ($data['from'] ?? '');

        return match ($to) {
            Ticket::SUB_STATUS_TRIAGE => [
                'label' => 'Fase Triase',
                'description' => 'Tiket memasuki fase triase',
                'color' => 'sky',
            ],
            Ticket::SUB_STATUS_ANALYSIS => [
                'label' => 'Analis Mulai Analisis',
                'description' => $actorName.' memulai analisis tiket',
                'color' => 'blue',
            ],
            Ticket::SUB_STATUS_RESPONSE => [
                'label' => $from === Ticket::SUB_STATUS_RESOLUTION
                    ? 'Fase Respons Dibuka Kembali'
                    : 'Responder Mulai Penanganan',
                'description' => $from === Ticket::SUB_STATUS_RESOLUTION
                    ? $actorName.' membuka kembali fase respons untuk tindakan tambahan'
                    : $actorName.' memulai penanganan respons',
                'color' => 'amber',
            ],
            Ticket::SUB_STATUS_RESOLUTION => [
                'label' => 'Fase Resolusi',
                'description' => 'Tiket memasuki fase resolusi',
                'color' => 'green',
            ],
            default => [
                'label' => 'Perubahan Sub-Status',
                'description' => ($from ?: '—').' → '.($to ?: '—'),
                'color' => 'zinc',
            ],
        };
    }

    private function syncAssignAnalystUserIdFromTicket(Ticket $ticket): void
    {
        $ticket->loadMissing(['assignments' => fn ($q) => $q->where('is_active', true)]);
        $primary = $ticket->assignments
            ->firstWhere('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY);
        $this->assignAnalystUserId = $primary !== null ? (string) $primary->user_id : null;
    }

    private function syncAssignResponderUserIdFromTicket(Ticket $ticket): void
    {
        $ticket->loadMissing(['assignments' => fn ($q) => $q->where('is_active', true)->with('user')]);
        $primary = $ticket->assignments->firstWhere('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY);
        $uid = $primary?->user_id;
        if ($uid !== null) {
            $primaryUser = $primary?->user ?? User::query()->find($uid);
            if ($primaryUser !== null && $primaryUser->can('ticket.respond')) {
                $this->assignResponderUserId = (string) $uid;

                return;
            }
        }
        $this->assignResponderUserId = null;
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
    private function resolveDetailTicket(): ?Ticket
    {
        if ($this->detailTicketPublicId === null || $this->detailTicketPublicId === '') {
            return null;
        }

        return Ticket::query()
            ->where('public_id', $this->detailTicketPublicId)
            ->first();
    }

    private function resolveDetailTicketOrFlash(): ?Ticket
    {
        $ticket = $this->resolveDetailTicket();

        if ($ticket === null) {
            session()->flash('toast_error', 'Tiket tidak ditemukan. Silakan muat ulang halaman.');
            $this->detailTicketPublicId = null;
        }

        return $ticket;
    }

    private function createTicketRules(): array
    {
        return [
            'formTitle' => ['required', 'string', 'max:255'],
            'formReporterName' => ['required', 'string', 'max:255', 'regex:/^[\pL\s\.\-\']+$/u'],
            'formReporterEmail' => ['required', 'email', 'max:255'],
            'formReporterPhone' => ['nullable', 'string', 'max:15', 'regex:/^(\+62|62|08)\d+$/'],
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
                    'organization',
                    'evidences',
                    'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
                    'analyses' => fn ($q) => $q->latest('created_at')->with(['performer', 'iocs.iocType']),
                    'responseActions' => fn ($q) => $q->latest('created_at')->with('performer'),
                ])
                ->first();
        }

        $ticketTimeline = $detailTicket !== null
            ? $this->buildTicketTimeline($detailTicket)
            : collect();

        return view('livewire.pages.tickets.index-page', [
            'tickets' => $tickets,
            'categories' => IncidentCategory::query()->orderBy('name')->get(),
            'organizations' => Organization::query()->orderBy('name')->get(),
            'detailTicket' => $detailTicket,
            'ticketTimeline' => $ticketTimeline,
            'analysts' => User::role('analis')->orderBy('name')->get(),
            'responders' => User::permission('ticket.respond')->orderBy('name')->get(),
            'analystListMode' => $this->isAnalystListMode(),
            'responderListMode' => $this->isResponderListMode(),
        ]);
    }
}
