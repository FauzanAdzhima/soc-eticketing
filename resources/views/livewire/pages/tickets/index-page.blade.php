@php
    $tableColCount = 7;
    if ($analystListMode || $responderListMode) {
        $tableColCount = 8;
    }
    $showClosedAtColumn = auth()->user()?->hasRole('koordinator') && !$analystListMode && !$responderListMode;
    if ($showClosedAtColumn) {
        $tableColCount++;
    }
@endphp

<div class="space-y-4" x-data="{
    confirmTitle: 'Konfirmasi',
    confirmMessage: '',
    confirmVariant: 'danger',
    confirmBusy: false,
    confirmAction: null,
    showConfirm: false,
    onConfirmDialogClose() {
        this.confirmBusy = false;
        this.confirmAction = null;
        this.confirmMessage = '';
        this.confirmTitle = 'Konfirmasi';
        this.showConfirm = false;
        const container = this.$refs.ticketDetailScrollContainer;
        if (container) {
            container.style.overflowY = '';
        }
    },
    openConfirm({ title = 'Konfirmasi', message = '', variant = 'danger', action = null }) {
        this.confirmTitle = title;
        this.confirmMessage = message;
        this.confirmVariant = variant;
        this.confirmAction = action;
        this.confirmBusy = false;
        this.showConfirm = true;
        this.$nextTick(() => {
            const container = this.$refs.ticketDetailScrollContainer;
            if (container && typeof container.scrollTo === 'function') {
                container.scrollTo({ top: 0, behavior: 'smooth' });
                container.style.overflowY = 'hidden';
            }
        });
    },
    closeConfirm() {
        this.onConfirmDialogClose();
    },
    async runConfirm() {
        if (!this.confirmAction || this.confirmBusy) return;
        this.confirmBusy = true;
        try {
            await this.confirmAction();
        } finally {
            this.closeConfirm();
        }
    },
}">
    @if ($analystListMode && auth()->user()?->can('ticket.analyze'))
        <div wire:poll.20s="refreshAssignmentSignal" class="hidden" aria-hidden="true"></div>
    @endif

    @if ($responderListMode && auth()->user()?->can('ticket.respond'))
        <div wire:poll.20s="refreshResponderAssignmentSignal" class="hidden" aria-hidden="true"></div>
    @endif


    @if ($analystListMode && $showNewAssignmentBanner)
        <div class="rounded-lg border border-sky-200 bg-sky-50 px-4 py-3 text-sm text-sky-900 shadow-sm dark:border-sky-700/50 dark:bg-sky-950/40 dark:text-sky-100"
            role="status">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="min-w-0 font-medium">Ada penugasan baru untuk Anda.</p>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="primary"
                        href="{{ route('tickets.index', ['scope' => 'analyst']) }}" wire:navigate>
                        Buka daftar tiket
                    </flux:button>
                    <flux:button type="button" size="sm" variant="ghost" wire:click="dismissNewAssignmentBanner">
                        Tutup
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    @if ($responderListMode && $showResponderNewAssignmentBanner)
        <div class="rounded-lg border border-blue-200 bg-blue-50 px-4 py-3 text-sm text-blue-900 shadow-sm dark:border-blue-800/50 dark:bg-blue-950/40 dark:text-blue-100"
            role="status">
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="min-w-0 font-medium">Ada penugasan penanganan baru untuk Anda.</p>
                <div class="flex flex-wrap items-center gap-2">
                    <flux:button size="sm" variant="primary"
                        href="{{ route('tickets.index', ['scope' => 'responder']) }}" wire:navigate>
                        Buka daftar tiket
                    </flux:button>
                    <flux:button type="button" size="sm" variant="ghost"
                        wire:click="dismissResponderNewAssignmentBanner">
                        Tutup
                    </flux:button>
                </div>
            </div>
        </div>
    @endif

    @if (!$detailTicket && session()->has('toast_success'))
        <div x-data="{ open: true }" x-init="window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => open = false, 5000)" x-show="open"
            class="rounded-lg border border-emerald-300 bg-emerald-100 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-100">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none">&times;</button>
            </div>
        </div>
    @endif
    @if (!$detailTicket && session()->has('toast_error'))
        <div x-data="{ open: true }" x-init="window.scrollTo({ top: 0, behavior: 'smooth' });
        setTimeout(() => open = false, 5000)" x-show="open"
            class="rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-900 dark:border-red-700 dark:bg-red-900/40 dark:text-red-100">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_error') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">
                @if ($analystListMode)
                    Analisis Tiket
                @elseif ($responderListMode)
                    Penanganan Tiket
                @else
                    Daftar Tiket
                @endif
            </flux:heading>
            @if ($analystListMode)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tiket yang ditugaskan kepada Anda.</p>
            @elseif ($responderListMode)
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tiket yang telah dianalisis dan memerlukan atau
                    sedang dalam penanganan respons.</p>
            @endif
        </div>
        @can('ticket.create.pic')
            <flux:button size="sm" variant="primary" wire:click="openCreateModal">Buat Tiket</flux:button>
        @endcan
    </div>

    @if ($responderListMode)
        <flux:card class="p-4 sm:p-5">
            <div class="grid gap-4 sm:grid-cols-2 lg:max-w-3xl">
                <flux:select label="Filter fase" wire:model.live="responderFilterStatus">
                    <flux:select.option value="all">Semua</flux:select.option>
                    <flux:select.option value="ready_for_response">Siap ditangani</flux:select.option>
                    <flux:select.option value="in_progress">Ditangani</flux:select.option>
                    <flux:select.option value="resolved">Selesai ditangani</flux:select.option>
                </flux:select>
                <flux:select label="Filter keparahan (analisis terbaru)" wire:model.live="responderFilterSeverity">
                    <flux:select.option value="all">Semua</flux:select.option>
                    <flux:select.option value="Low">Low</flux:select.option>
                    <flux:select.option value="Medium">Medium</flux:select.option>
                    <flux:select.option value="High">High</flux:select.option>
                    <flux:select.option value="Critical">Critical</flux:select.option>
                </flux:select>
            </div>
        </flux:card>
    @endif

    <flux:card class="min-w-0 p-4 sm:p-5">
        <div class="ticket-list-scrollbar max-w-full overflow-x-auto overscroll-x-contain rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="w-full min-w-max divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">No. Tiket</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Judul</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Status</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Kategori</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Petugas Tiket
                        </th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Dibuat</th>
                        @if ($showClosedAtColumn)
                            <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Ditutup</th>
                        @endif
                        @if ($analystListMode)
                            <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Sudah
                                Dianalisis</th>
                        @endif
                        @if ($responderListMode)
                            <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Fase</th>
                        @endif
                        <th class="px-3 py-2 pr-9 text-right font-semibold text-zinc-700 dark:text-zinc-200">
                            <span class="inline-block pl-3">Aksi</span>
                        </th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($tickets as $ticket)
                        @php
                            $assignedNames = $ticket->assignments
                                ->where('is_active', true)
                                ->map(fn($a) => $a->user?->name)
                                ->filter()
                                ->unique()
                                ->join(', ');
                            $coordinatorBadge = $ticket->coordinatorBadge();
                            $__badgeLabelMap = trans('tickets.coordinator_badge_labels', [], 'id');
                            $coordinatorBadgeLabel = is_array($__badgeLabelMap) &&
                                array_key_exists($coordinatorBadge['label'], $__badgeLabelMap)
                                    ? $__badgeLabelMap[$coordinatorBadge['label']]
                                    : $coordinatorBadge['label'];
                        @endphp
                        <tr wire:key="ticket-{{ $ticket->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-3 py-2">
                                <span
                                    title="Severity: {{ $ticket->incident_severity ?? '—' }}"
                                    class="inline-flex max-w-full items-center rounded-full px-2.5 py-0.5 font-mono text-xs font-semibold {{ $ticket->incidentSeverityTicketNumberPillClasses() }}">
                                    {{ $ticket->ticket_number ?? '—' }}
                                </span>
                            </td>
                            <td class="max-w-xs whitespace-nowrap px-3 py-2 text-zinc-900 dark:text-zinc-100"
                                title="{{ $ticket->title }}">
                                <span class="block truncate">{{ $ticket->title }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2">
                                <span
                                    class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $coordinatorBadge['badge_class'] }}">
                                    {{ $coordinatorBadgeLabel }}
                                </span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                {{ $ticket->category?->name ?? '—' }}</td>
                            <td class="max-w-[14rem] whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300"
                                title="{{ $assignedNames }}">
                                <span class="block truncate">{{ $assignedNames !== '' ? $assignedNames : '—' }}</span>
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                {{ $ticket->created_at?->format('d M Y H:i') }}
                            </td>
                            @if ($showClosedAtColumn)
                                <td class="whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300">
                                    {{ $ticket->closed_at?->format('d M Y H:i') ?? '—' }}
                                </td>
                            @endif
                            @if ($analystListMode)
                                <td class="whitespace-nowrap px-3 py-2 text-zinc-700 dark:text-zinc-200">
                                    @if (!empty($ticket->analyses_exists))
                                        <span
                                            class="inline-flex rounded-full bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-800 dark:bg-emerald-900/40 dark:text-emerald-300">Ya</span>
                                    @else
                                        <span
                                            class="inline-flex rounded-full bg-zinc-100 px-2 py-0.5 text-xs font-medium text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">Belum</span>
                                    @endif
                                </td>
                            @endif
                            @if ($responderListMode)
                                @php
                                    $phaseRow = $ticket->responderWorkPhase(
                                        isset($ticket->response_actions_count)
                                            ? (int) $ticket->response_actions_count
                                            : null,
                                    );
                                @endphp
                                <td class="whitespace-nowrap px-3 py-2 text-zinc-700 dark:text-zinc-200">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $phaseRow['badge_class'] }}">{{ $phaseRow['label'] }}</span>
                                </td>
                            @endif
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <div class="flex flex-nowrap items-center justify-end gap-1">
                                    @can('analyze', $ticket)
                                        @if ($analystListMode || !auth()->user()?->shouldHideAnalysisShortcutOnMainTicketList())
                                            <flux:button type="button" size="sm" variant="primary"
                                                href="{{ route('tickets.analysis', $ticket) }}" wire:navigate>
                                                Analisis
                                            </flux:button>
                                        @endif
                                    @endcan
                                    @can('respond', $ticket)
                                        <flux:button type="button" size="sm" variant="primary"
                                            href="{{ route('tickets.respond', $ticket) }}" wire:navigate>
                                            Penanganan
                                        </flux:button>
                                    @endcan
                                    <flux:button type="button" size="sm" variant="ghost"
                                        wire:click="openTicketDetail('{{ $ticket->public_id }}')">Detail</flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="{{ $tableColCount }}"
                                class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">Tidak ada tiket.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="mt-4">
            {{ $tickets->links() }}
        </div>
    </flux:card>

    @can('ticket.create.pic')
        <flux:modal name="ticket-create-pic-modal" class="md:w-[800px]">
            <div class="space-y-6" wire:key="create-ticket-step-{{ $createTicketShowFullForm ? 'form' : 'categories' }}">
                @if (!$createTicketShowFullForm)
                    <header>
                        <flux:heading size="xl">Pilih kategori insiden</flux:heading>
                        <flux:subheading>Pilih jenis kejadian yang paling sesuai. Anda masih bisa mengubah kategori di
                            langkah berikutnya.</flux:subheading>
                    </header>

                    @if ($categories->isEmpty())
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Belum ada kategori insiden yang tersedia.
                            Hubungi administrator.</flux:text>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($categories as $cat)
                                <button type="button" wire:click="selectCreateTicketCategory({{ $cat->id }})"
                                    wire:key="create-pick-cat-{{ $cat->id }}"
                                    class="group flex min-h-[5.5rem] w-full flex-col rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-xs transition hover:border-sky-400 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-sky-500/60 dark:hover:bg-zinc-800/80">
                                    <span
                                        class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $cat->name }}</span>
                                    @if (filled($cat->description))
                                        <span
                                            class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $cat->description }}</span>
                                    @else
                                        <span
                                            class="mt-auto pt-2 text-xs font-medium text-sky-600 opacity-0 transition group-hover:opacity-100 dark:text-sky-400">Pilih
                                            kategori ini →</span>
                                    @endif
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex justify-end border-t border-zinc-200 pt-6 dark:border-zinc-700">
                        <flux:button type="button" variant="ghost" wire:click="closeCreateModal">Batal</flux:button>
                    </div>
                @else
                    <header>
                        <flux:heading size="xl">Buat tiket</flux:heading>
                        <flux:subheading>Laporan insiden baru (akun PIC). Data pelapor dapat disesuaikan sebelum dikirim.
                        </flux:subheading>
                    </header>

                    <form wire:submit="createTicket" class="space-y-6">
                        <flux:select label="Kategori insiden" wire:model="formIncidentCategoryId" searchable>
                            @foreach ($categories as $cat)
                                <flux:select.option value="{{ $cat->id }}">{{ $cat->name }}</flux:select.option>
                            @endforeach
                        </flux:select>
                        @error('formIncidentCategoryId')
                            <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</span>
                        @enderror

                        <flux:input label="Subjek / judul" wire:model="formTitle"
                            placeholder="contoh: Indikasi serangan pada sistem X" required />

                        <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                            <flux:input label="Nama pelapor" wire:model="formReporterName" icon="user" required />
                            <flux:input label="No. WhatsApp / telepon" wire:model="formReporterPhone" icon="phone" />

                            <div
                                class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 md:col-span-2 dark:hover:bg-zinc-800/50">
                                <flux:checkbox wire:model.live="isOfficialEmployee"
                                    label="Pelapor pegawai / ASN (pakai organisasi terdaftar)" />
                            </div>

                            <flux:input type="email" label="Email pelapor" wire:model="formReporterEmail"
                                icon="envelope" required />

                            <div wire:key="ticket-create-org-field">
                                @if ($isOfficialEmployee)
                                    <flux:select label="Instansi / organisasi" wire:model="formReporterOrganizationId"
                                        icon="building-office" searchable>
                                        @foreach ($organizations as $org)
                                            <flux:select.option value="{{ $org->id }}">{{ $org->name }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                @else
                                    <flux:input label="Instansi / organisasi (teks)"
                                        wire:model="formReporterOrganizationName" icon="building-office-2"
                                        placeholder="contoh: Universitas X" />
                                @endif
                            </div>
                            @error('formReporterOrganizationId')
                                <span
                                    class="text-sm font-medium text-red-600 dark:text-red-400 md:col-span-2">{{ $message }}</span>
                            @enderror

                            <flux:separator class="md:col-span-2" />

                            <flux:select label="Tingkat keparahan" wire:model="formIncidentSeverity">
                                <flux:select.option value="Low">Rendah (Low)</flux:select.option>
                                <flux:select.option value="Medium">Sedang (Medium)</flux:select.option>
                                <flux:select.option value="High">Tinggi (High)</flux:select.option>
                                <flux:select.option value="Critical">Kritis (Critical)</flux:select.option>
                            </flux:select>

                            <flux:input type="datetime-local" label="Waktu kejadian" wire:model="formIncidentTime" />

                            <div class="md:col-span-2">
                                <flux:textarea label="Deskripsi kejadian" wire:model="formIncidentDescription"
                                    rows="5" placeholder="Kronologi singkat kejadian…" />
                            </div>

                            <div class="space-y-3 border-t border-zinc-100 pt-4 dark:border-zinc-800 md:col-span-2">
                                <flux:label>Bukti dukung (opsional)</flux:label>
                                <input type="file" wire:model="evidenceFiles" multiple
                                    class="block w-full cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 text-sm text-zinc-600 file:mr-4 file:rounded-l-lg file:border-0 file:bg-zinc-200 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-zinc-800 hover:file:bg-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-100" />
                                <div wire:loading wire:target="evidenceFiles"
                                    class="text-xs text-sky-600 animate-pulse dark:text-sky-400">
                                    Mengunggah berkas…
                                </div>
                                @error('evidenceFiles.*')
                                    <span
                                        class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</span>
                                @enderror

                                @if (!empty($evidenceFiles))
                                    <div class="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                                        @foreach ($evidenceFiles as $index => $evidence)
                                            <div class="space-y-2 rounded border p-2 text-xs"
                                                wire:key="pic-evidence-{{ $index }}">
                                                @if ($this->isImageFile($evidence))
                                                    <img src="{{ $evidence->temporaryUrl() }}" alt="Pratinjau"
                                                        class="h-24 w-full rounded object-cover">
                                                @else
                                                    <div
                                                        class="flex h-24 w-full items-center justify-center rounded bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
                                                        FILE
                                                    </div>
                                                @endif
                                                <div class="truncate">{{ $this->evidenceOriginalName($evidence) }}</div>
                                                <div class="text-zinc-400">{{ $this->evidenceSizeKb($evidence) }} KB</div>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="flex border-t border-zinc-200 pt-6 dark:border-zinc-700">
                            <flux:spacer />
                            <flux:button type="button" variant="ghost" wire:click="closeCreateModal">Batal</flux:button>
                            <flux:button type="submit" variant="primary" class="ml-3" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="createTicket">Simpan tiket</span>
                                <span wire:loading wire:target="createTicket">Menyimpan…</span>
                            </flux:button>
                        </div>
                    </form>
                @endif
            </div>
        </flux:modal>
    @endcan

    <flux:modal name="ticket-detail-modal" class="md:w-[1100px]" :closable="false"
        wire:close="closeTicketDetail">
        @if ($detailTicket)
            @php
                $assignedNames = $detailTicket->assignments
                    ->where('is_active', true)
                    ->map(fn($a) => $a->user?->name)
                    ->filter()
                    ->unique()
                    ->join(', ');
                $coordinatorBadge = $detailTicket->coordinatorBadge();
                $readyForCoordinatorReassign =
                    $detailTicket->status === \App\Models\Ticket::STATUS_ON_PROGRESS &&
                    $detailTicket->sub_status === \App\Models\Ticket::SUB_STATUS_RESOLUTION &&
                    $detailTicket->report_status === \App\Models\Ticket::REPORT_STATUS_VERIFIED &&
                    $detailTicket->report_is_valid === true;
                $detailIncidentDescriptionDisplay = preg_replace(
                    '/^[ \t\x{00A0}]+/um',
                    '',
                    trim(str_replace("\xC2\xA0", ' ', (string) ($detailTicket->incident_description ?? ''))),
                );
                $detailReporterOrg =
                    $detailTicket->organization?->name ?? $detailTicket->reporter_organization_name;
            @endphp
            <div x-ref="ticketDetailScrollContainer" :class="showConfirm ? 'confirm-open' : ''" class="ticket-list-scrollbar relative max-h-[80vh] space-y-6 overflow-x-hidden overflow-y-auto overscroll-contain">
                <header
                    class="sticky top-0 z-10 -mx-1 overflow-hidden rounded-t-xl border-b border-zinc-200 bg-white/95 px-4 pb-3 pt-4 backdrop-blur sm:px-5 dark:border-zinc-700 dark:bg-zinc-900/95">
                    <div class="flex items-start justify-between gap-3">
                        <div class="min-w-0 flex-1 pr-2">
                            <flux:heading size="lg" class="line-clamp-3">{{ $detailTicket->title ?: '—' }}
                            </flux:heading>
                            <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">No. Tiket: {{ $detailTicket->ticket_number ?? '—' }}</p>
                        </div>
                        <flux:button type="button" variant="ghost" size="sm" icon="x-mark"
                            wire:click="closeTicketDetail" class="shrink-0" aria-label="Tutup" />
                    </div>
                    @can('analyze', $detailTicket)
                        @if ($analystListMode || !auth()->user()?->shouldHideAnalysisShortcutOnMainTicketList())
                            <div class="mt-3">
                                <flux:button href="{{ route('tickets.analysis', $detailTicket) }}" variant="primary"
                                    size="sm" wire:navigate>
                                    Buka Form Analisis Tiket
                                </flux:button>
                            </div>
                        @endif
                    @endcan
                </header>

                <div x-show="showConfirm" x-cloak
                    class="confirm-overlay absolute -inset-3 z-40 flex items-center justify-center p-4"
                    @keydown.escape.window="!confirmBusy && closeConfirm()">
                    <div
                        class="w-[min(100vw-2rem,32rem)] max-w-lg rounded-xl border border-zinc-200 bg-white p-5 shadow-2xl dark:border-zinc-700 dark:bg-zinc-900">
                        <div class="flex items-start justify-between gap-3">
                            <div class="min-w-0">
                                <p class="text-base font-semibold text-zinc-900 dark:text-zinc-100"
                                    x-text="confirmTitle"></p>
                                <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300" x-text="confirmMessage"></p>
                            </div>
                            <button type="button"
                                class="shrink-0 text-xl leading-none text-zinc-500 hover:text-zinc-900 dark:text-zinc-400 dark:hover:text-zinc-100"
                                aria-label="Tutup" @click="!confirmBusy && closeConfirm()">
                                &times;
                            </button>
                        </div>
                        <div class="mt-5 flex justify-end gap-2">
                            <flux:button type="button" variant="ghost" @click="!confirmBusy && closeConfirm()"
                                x-bind:disabled="confirmBusy">
                                Batal
                            </flux:button>
                            <flux:button type="button" variant="primary" @click="runConfirm()"
                                x-show="confirmVariant !== 'danger'" x-bind:disabled="confirmBusy">
                                <span x-show="!confirmBusy">Konfirmasi</span>
                                <span x-show="confirmBusy">Memproses…</span>
                            </flux:button>
                            <flux:button type="button" variant="danger" @click="runConfirm()"
                                x-show="confirmVariant === 'danger'" x-bind:disabled="confirmBusy">
                                <span x-show="!confirmBusy">Konfirmasi</span>
                                <span x-show="confirmBusy">Memproses…</span>
                            </flux:button>
                        </div>
                    </div>
                </div>

                @if (session()->has('toast_success'))
                    <div x-data="{ open: true }" x-init="$el.closest('.max-h-\\[80vh\\]')?.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => open = false, 5000)" x-show="open" class="px-1">
                        <div
                            class="rounded-lg border border-emerald-300 bg-emerald-100 px-4 py-3 text-sm text-emerald-900 dark:border-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-100">
                            <div class="flex items-start justify-between gap-3">
                                <span>{{ session('toast_success') }}</span>
                                <button type="button" @click="open = false" class="text-base leading-none"
                                    aria-label="Tutup">&times;</button>
                            </div>
                        </div>
                    </div>
                @endif
                @if (session()->has('toast_error'))
                    <div x-data="{ open: true }" x-init="$el.closest('.max-h-\\[80vh\\]')?.scrollTo({ top: 0, behavior: 'smooth' });
                    setTimeout(() => open = false, 5000)" x-show="open" class="px-1">
                        <div
                            class="rounded-lg border border-red-300 bg-red-100 px-4 py-3 text-sm text-red-900 dark:border-red-700 dark:bg-red-900/40 dark:text-red-100">
                            <div class="flex items-start justify-between gap-3">
                                <span>{{ session('toast_error') }}</span>
                                <button type="button" @click="open = false" class="text-base leading-none"
                                    aria-label="Tutup">&times;</button>
                            </div>
                        </div>
                    </div>
                @endif

                <details open
                    class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-zinc-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">Laporan Awal
                                Tiket</span>
                        </span>
                        <svg class="size-4 shrink-0 text-zinc-500 transition-transform duration-200 group-open:rotate-180 sm:size-5 dark:text-zinc-400"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="space-y-4 border-t border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status</dt>
                                <dd class="mt-0.5">
                                    <span
                                        class="inline-flex rounded-full px-2 py-0.5 text-xs font-medium {{ $coordinatorBadge['badge_class'] }}">
                                        {{ $coordinatorBadge['label'] }}
                                    </span>
                                </dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status Laporan</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->report_status ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Sub-Status</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->sub_status ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Laporan Valid</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->report_is_valid ? 'Ya' : 'Belum' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kategori</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->category?->name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Petugas Tiket</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $assignedNames !== '' ? $assignedNames : '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dibuat</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->created_at?->format('d M Y H:i') }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pembuat (internal)</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->creator?->name ?? '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pelapor</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->reporter_name ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Email Pelapor</dt>
                                <dd class="mt-0.5 break-all text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->reporter_email ?? '—' }}</dd>
                            </div>
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kontak Pelapor</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                    {{ $detailTicket->reporter_phone ?? '—' }}</dd>
                            </div>
                            @if (filled($detailReporterOrg))
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Instansi / Organisasi Pelapor</dt>
                                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailReporterOrg }}</dd>
                                </div>
                            @endif
                            <div class="min-w-0 sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Deskripsi Insiden</dt>
                                <dd
                                    class="ticket-detail-incident-desc mb-0 mt-0.5 w-full min-w-0 text-left text-zinc-900 dark:text-zinc-100"><span
                                        class="whitespace-pre-wrap break-words">{{ $detailIncidentDescriptionDisplay !== '' ? $detailIncidentDescriptionDisplay : '—' }}</span></dd>
                            </div>
                        </dl>
                        @if (filled($detailTicket->report_rejection_reason))
                            <div
                                class="mt-4 rounded-lg border border-red-200 bg-red-50/80 p-3 dark:border-red-900/50 dark:bg-red-950/20">
                                <p class="text-sm font-medium text-red-800 dark:text-red-300">Alasan penolakan laporan
                                </p>
                                <p class="mt-1 text-sm text-red-900 dark:text-red-100"><span
                                        class="whitespace-pre-wrap break-words">{{ $detailTicket->report_rejection_reason }}</span></p>
                            </div>
                        @endif
                        <div class="space-y-4">
                            <flux:heading size="lg">Bukti dukung</flux:heading>
                            @if ($detailTicket->evidences->isEmpty())
                                <flux:text class="text-zinc-500 dark:text-zinc-400">Tidak ada lampiran pada tiket ini.
                                </flux:text>
                            @else
                                <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                                    @foreach ($detailTicket->evidences as $evidence)
                                        @php
                                            $evidenceUrl = route('tickets.evidence.show', $evidence);
                                            $sizeKb = $evidence->size
                                                ? number_format((int) $evidence->size / 1024, 1)
                                                : null;
                                        @endphp
                                        <li class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80"
                                            wire:key="evidence-{{ $evidence->id }}">
                                            <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer"
                                                class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                                                <div
                                                    class="relative h-28 w-full overflow-hidden bg-zinc-200 sm:h-32 dark:bg-zinc-700">
                                                    @if ($evidence->isLikelyImage())
                                                        <img src="{{ $evidenceUrl }}"
                                                            alt="{{ $evidence->original_name ?? 'Bukti gambar' }}"
                                                            class="h-full w-full object-cover" loading="lazy"
                                                            decoding="async" fetchpriority="low">
                                                    @else
                                                        <div
                                                            class="flex h-full w-full items-center justify-center text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                            {{ strtoupper(\Illuminate\Support\Str::afterLast($evidence->original_name ?? 'file', '.')) ?: 'FILE' }}
                                                        </div>
                                                    @endif
                                                </div>
                                                <div class="border-t border-zinc-200 p-2 dark:border-zinc-600">
                                                    <p class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-100"
                                                        title="{{ $evidence->original_name }}">
                                                        {{ $evidence->original_name ?? 'Lampiran' }}</p>
                                                    @if ($sizeKb !== null)
                                                        <p class="text-[11px] text-zinc-500 dark:text-zinc-400">
                                                            {{ $sizeKb }} KB</p>
                                                    @endif
                                                </div>
                                            </a>
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </div>
                </details>

                <details
                    class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-zinc-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">Linimasa Tiket</span>
                        </span>
                        <svg class="size-4 shrink-0 text-zinc-500 transition-transform duration-200 group-open:rotate-180 sm:size-5 dark:text-zinc-400"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="border-t border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        @if ($ticketTimeline->isEmpty())
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada riwayat.</p>
                        @else
                            <ol class="relative border-s-2 border-zinc-200 dark:border-zinc-700">
                                @foreach ($ticketTimeline as $tl)
                                    @php
                                        $dotClasses = match ($tl['color']) {
                                            'sky' => 'bg-sky-500 dark:bg-sky-400',
                                            'emerald' => 'bg-emerald-500 dark:bg-emerald-400',
                                            'green' => 'bg-green-500 dark:bg-green-400',
                                            'red' => 'bg-red-500 dark:bg-red-400',
                                            'violet' => 'bg-violet-500 dark:bg-violet-400',
                                            'purple' => 'bg-purple-500 dark:bg-purple-400',
                                            'blue' => 'bg-blue-500 dark:bg-blue-400',
                                            'amber' => 'bg-amber-500 dark:bg-amber-400',
                                            default => 'bg-zinc-400 dark:bg-zinc-500',
                                        };
                                    @endphp
                                    <li class="ms-6 {{ !$loop->last ? 'pb-6' : '' }}">
                                        <span
                                            class="absolute -start-[7px] mt-1 flex h-3 w-3 items-center justify-center rounded-full ring-[3px] ring-white dark:ring-zinc-900 {{ $dotClasses }}"></span>
                                        <div class="min-w-0">
                                            <div class="flex flex-wrap items-center gap-x-2.5 gap-y-1">
                                                <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                                    {{ $tl['label'] }}</h3>
                                                @if (!empty($tl['elapsed_text']))
                                                    <span
                                                        class="inline-flex items-center gap-1 rounded-full bg-zinc-100 px-2 py-0.5 text-[11px] font-medium text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                                                        <svg class="h-3 w-3" xmlns="http://www.w3.org/2000/svg"
                                                            fill="none" viewBox="0 0 24 24" stroke-width="1.5"
                                                            stroke="currentColor">
                                                            <path stroke-linecap="round" stroke-linejoin="round"
                                                                d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                                                        </svg>
                                                        +{{ $tl['elapsed_text'] }}
                                                    </span>
                                                @endif
                                            </div>
                                            <time
                                                class="mt-0.5 block text-xs text-zinc-500 dark:text-zinc-400">{{ $tl['at']?->format('d M Y H:i:s') ?? '—' }}</time>
                                            <p class="mt-1 text-sm text-zinc-600 dark:text-zinc-300">
                                                {{ $tl['description'] }}</p>
                                        </div>
                                    </li>
                                @endforeach
                            </ol>
                        @endif
                    </div>
                </details>

                <details
                    class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-zinc-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">Analisis
                                Tiket</span>
                        </span>
                        <svg class="size-4 shrink-0 text-zinc-500 transition-transform duration-200 group-open:rotate-180 sm:size-5 dark:text-zinc-400"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="space-y-4 border-t border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        @forelse ($detailTicket->analyses as $analysis)
                            <div class="space-y-3 rounded-lg border border-zinc-200 p-3 dark:border-zinc-700">
                                <div class="text-sm text-zinc-600 dark:text-zinc-300">
                                    <span class="font-medium text-zinc-900 dark:text-zinc-100">Analis:</span>
                                    {{ $analysis->performer?->name ?? '—' }}
                                </div>
                                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                                    <div>
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Severity</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                            {{ $analysis->severity ?? '—' }}</dd>
                                    </div>
                                    <div>
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Waktu</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                            {{ $analysis->created_at?->format('d M Y H:i') ?? '—' }}</dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Hasil analisis</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100"><span
                                                class="whitespace-pre-wrap break-words">{{ $analysis->analysis_result ?? '—' }}</span></dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dampak</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100"><span
                                                class="whitespace-pre-wrap break-words">{{ $analysis->impact ?? '—' }}</span></dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Akar masalah</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100"><span
                                                class="whitespace-pre-wrap break-words">{{ $analysis->root_cause ?? '—' }}</span></dd>
                                    </div>
                                    <div class="sm:col-span-2">
                                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Rekomendasi</dt>
                                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100"><span
                                                class="whitespace-pre-wrap break-words">{{ $analysis->recommendation ?? '—' }}</span></dd>
                                    </div>
                                </dl>
                                <div class="space-y-2">
                                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-200">IOC</p>
                                    @if ($analysis->iocs->isEmpty())
                                        <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada IOC pada analisis
                                            ini.</p>
                                    @else
                                        <ul class="space-y-2">
                                            @foreach ($analysis->iocs as $ioc)
                                                <li
                                                    class="rounded-lg border border-zinc-200 bg-zinc-50 px-3 py-2 text-sm dark:border-zinc-700 dark:bg-zinc-800/60">
                                                    <p class="font-medium text-zinc-800 dark:text-zinc-100">
                                                        {{ $ioc->iocType?->name ?? 'Tipe IOC' }}: {{ $ioc->value }}
                                                    </p>
                                                    @if (filled($ioc->description))
                                                        <p class="mt-0.5 text-zinc-600 dark:text-zinc-300">
                                                            {{ $ioc->description }}</p>
                                                    @endif
                                                </li>
                                            @endforeach
                                        </ul>
                                    @endif
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada data analisis.</p>
                        @endforelse
                    </div>
                </details>

                <details
                    class="group rounded-xl border border-zinc-200 bg-white dark:border-zinc-700 dark:bg-zinc-900">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-zinc-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden">
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">Tindakan Yang
                                Dilakukan</span>
                        </span>
                        <svg class="size-4 shrink-0 text-zinc-500 transition-transform duration-200 group-open:rotate-180 sm:size-5 dark:text-zinc-400"
                            xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"
                            aria-hidden="true">
                            <path fill-rule="evenodd"
                                d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z"
                                clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="space-y-3 border-t border-zinc-200 p-4 dark:border-zinc-700 sm:p-5">
                        @forelse ($detailTicket->responseActions as $action)
                            <div class="rounded-lg border border-zinc-200 p-3 text-sm dark:border-zinc-700">
                                <p class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ ucfirst((string) $action->action_type) }} oleh
                                    {{ $action->performer?->name ?? '—' }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $action->created_at?->format('d M Y H:i') ?? '—' }}</p>
                                <p class="mt-2 text-zinc-700 dark:text-zinc-200"><span
                                        class="whitespace-pre-wrap break-words">{{ $action->description }}</span></p>
                            </div>
                        @empty
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">Belum ada catatan tindakan respons.</p>
                        @endforelse
                    </div>
                </details>

                @can('ticket.chat.view')
                    <div class="px-1">
                        <div class="mb-2 flex flex-wrap items-center justify-between gap-2">
                            <flux:heading size="lg">Diskusi / Chat</flux:heading>
                            <flux:button type="button" size="sm" variant="ghost" href="{{ route('tickets.chat', $detailTicket) }}"
                                wire:navigate>
                                Buka layar penuh
                            </flux:button>
                        </div>
                        @livewire('ticket.chat', ['ticket' => $detailTicket], key('ticket-chat-detail-' . $detailTicket->id))
                    </div>
                @endcan

                @can('verifyReport', $detailTicket)
                    <flux:card class="space-y-4 border-dashed border-amber-200 p-4 sm:p-5 dark:border-amber-800/50">
                        <div class="space-y-3">
                            <flux:heading size="lg">Verifikasi Laporan</flux:heading>
                            <flux:subheading>
                                Verifikasi jika laporan valid dan dapat dilanjutkan ke analis. Tolak jika laporan tidak
                                valid, tidak relevan, atau indikasi false report—tindakan penolakan bersifat final dan
                                memerlukan alasan.
                            </flux:subheading>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <flux:button type="button" variant="primary" wire:click="verifyTicketReport"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="verifyTicketReport">Verifikasi</span>
                                <span wire:loading wire:target="verifyTicketReport">Memproses…</span>
                            </flux:button>
                            <flux:button type="button" variant="danger" wire:click="openRejectReportPanel"
                                wire:loading.attr="disabled">
                                Tolak
                            </flux:button>
                        </div>

                        @if ($showRejectReportPanel)
                            <div
                                class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-800/50 dark:bg-amber-950/25">
                                <p class="text-sm text-amber-900 dark:text-amber-100">
                                    Penolakan bersifat final: tiket tidak akan ditugaskan ke analis. Wajib mengisi alasan
                                    yang jelas (minimal 15 karakter) sebelum mengonfirmasi.
                                </p>
                                <flux:textarea wire:model="rejectReportReason" rows="4" label="Alasan penolakan"
                                    placeholder="Jelaskan singkat mengapa laporan ditolak (wajib, minimal 15 karakter)." />
                                @error('rejectReportReason')
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <div class="flex flex-wrap items-center gap-3">
                                    <flux:button type="button" variant="ghost" wire:click="cancelRejectReport">
                                        Batal
                                    </flux:button>
                                    <flux:button type="button" variant="danger"
                                        @click="openConfirm({
                                            title: 'Tolak laporan?',
                                            message: 'Laporan akan ditolak dan tiket ditutup untuk alur ini. Lanjutkan?',
                                            variant: 'danger',
                                            action: async () => { await $wire.rejectTicketReport(); }
                                        })"
                                        wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="rejectTicketReport">Konfirmasi tolak
                                            laporan</span>
                                        <span wire:loading wire:target="rejectTicketReport">Memproses…</span>
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @endcan

                @if ($readyForCoordinatorReassign)
                    @canany(['assign', 'assignResponderHandoff'], $detailTicket)
                        <flux:card class="space-y-4 border-dashed border-sky-200 p-4 sm:p-5 dark:border-sky-800/50">
                            <flux:heading size="lg">Ulangi Penanganan Tiket</flux:heading>
                            <flux:subheading>Pilih tujuan penugasan ulang. Hanya satu panel form aktif pada satu waktu.
                            </flux:subheading>
                            <div class="flex flex-wrap items-center gap-2">
                                @can('assign', $detailTicket)
                                    <flux:button type="button"
                                        variant="{{ $reassignMode === 'analyst' ? 'primary' : 'ghost' }}"
                                        wire:click="showReassignAnalyst">
                                        Kembalikan ke Analis Keamanan
                                    </flux:button>
                                @endcan
                                @can('assignResponderHandoff', $detailTicket)
                                    <flux:button type="button"
                                        variant="{{ $reassignMode === 'responder' ? 'primary' : 'ghost' }}"
                                        wire:click="showReassignResponder">
                                        Kembalikan ke Responder Insiden
                                    </flux:button>
                                @endcan
                            </div>

                            @if ($reassignMode === 'analyst')
                                @can('assign', $detailTicket)
                                    <form wire:submit.prevent="assignAnalyst" class="space-y-4"
                                        wire:key="assign-analyst-{{ $detailTicket->public_id }}-{{ $detailTicket->report_status }}">
                                        <div class="space-y-2"
                                            wire:key="assign-analyst-field-{{ $detailTicket->public_id }}">
                                            <flux:select label="Analis"
                                                description="Pilih dari daftar (nama dan email). Analis yang dipilih menjadi penanggung jawab utama penanganan tiket."
                                                wire:model.live="assignAnalystUserId" icon="user">
                                                <option value="" wire:key="assign-analyst-empty">Pilih analis…</option>
                                                @foreach ($analysts as $analyst)
                                                    <flux:select.option value="{{ $analyst->id }}">
                                                        {{ $analyst->name }} — {{ $analyst->email }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="assignAnalyst">Teruskan Tiket</span>
                                                <span wire:loading wire:target="assignAnalyst">Menyimpan…</span>
                                            </flux:button>
                                        </div>
                                    </form>
                                @endcan
                            @elseif ($reassignMode === 'responder')
                                @can('assignResponderHandoff', $detailTicket)
                                    <form wire:submit.prevent="assignHandoffResponder" class="space-y-4"
                                        wire:key="assign-responder-{{ $detailTicket->public_id }}">
                                        <div class="space-y-2">
                                            <flux:select label="Responder" wire:model.live="assignResponderUserId"
                                                icon="user">
                                                <option value="">Pilih responder…</option>
                                                @foreach ($responders as $responderUser)
                                                    <flux:select.option value="{{ $responderUser->id }}">
                                                        {{ $responderUser->name }} — {{ $responderUser->email }}
                                                    </flux:select.option>
                                                @endforeach
                                            </flux:select>
                                            @error('assignResponderUserId')
                                                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}
                                                </p>
                                            @enderror
                                        </div>
                                        <div class="flex justify-end gap-2">
                                            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                                <span wire:loading.remove wire:target="assignHandoffResponder">Tugaskan
                                                    responder</span>
                                                <span wire:loading wire:target="assignHandoffResponder">Menyimpan…</span>
                                            </flux:button>
                                        </div>
                                    </form>
                                @endcan
                            @endif
                        </flux:card>
                    @endcanany
                @else
                    @can('assign', $detailTicket)
                        <flux:card class="space-y-4 border-dashed p-4 sm:p-5">
                            <flux:heading size="lg">Teruskan ke Analis Keamanan</flux:heading>
                            <flux:subheading>Pilih analis utama untuk penanganan tiket ini.</flux:subheading>
                            <form wire:submit.prevent="assignAnalyst" class="space-y-3"
                                wire:key="assign-analyst-{{ $detailTicket->public_id }}-{{ $detailTicket->report_status }}">
                                <div wire:key="assign-analyst-field-{{ $detailTicket->public_id }}">
                                    <flux:select wire:model.live="assignAnalystUserId" icon="user">
                                        <option value="" wire:key="assign-analyst-empty">Pilih analis…</option>
                                        @foreach ($analysts as $analyst)
                                            <flux:select.option value="{{ $analyst->id }}">
                                                {{ $analyst->name }} — {{ $analyst->email }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('assignAnalystUserId')
                                        <p class="mt-1.5 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}
                                        </p>
                                    @enderror
                                </div>
                                <div class="flex justify-end gap-2">
                                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="assignAnalyst">Teruskan Tiket</span>
                                        <span wire:loading wire:target="assignAnalyst">Menyimpan…</span>
                                    </flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @endcan

                    @can('assignResponderHandoff', $detailTicket)
                        <flux:card class="space-y-4 border-dashed border-sky-200 p-4 sm:p-5 dark:border-sky-800/50">
                            <flux:heading size="lg">Teruskan ke Responder Insiden</flux:heading>
                            <flux:subheading>Setelah analisis tersedia, tugaskan pengguna dengan izin penanganan respons
                                sebagai penanggung jawab utama.</flux:subheading>
                            <form wire:submit.prevent="assignHandoffResponder" class="space-y-4"
                                wire:key="assign-responder-{{ $detailTicket->public_id }}">
                                <div class="space-y-2">
                                    <flux:select label="Responder" wire:model.live="assignResponderUserId"
                                        icon="user">
                                        <option value="">Pilih responder…</option>
                                        @foreach ($responders as $responderUser)
                                            <flux:select.option value="{{ $responderUser->id }}">
                                                {{ $responderUser->name }} — {{ $responderUser->email }}
                                            </flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('assignResponderUserId')
                                        <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="flex justify-end gap-2">
                                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                                        <span wire:loading.remove wire:target="assignHandoffResponder">Teruskan Tiket</span>
                                        <span wire:loading wire:target="assignHandoffResponder">Menyimpan…</span>
                                    </flux:button>
                                </div>
                            </form>
                        </flux:card>
                    @endcan
                @endif

                @can('close', $detailTicket)
                    @if (
                        $detailTicket->status === \App\Models\Ticket::STATUS_ON_PROGRESS &&
                            $detailTicket->sub_status === \App\Models\Ticket::SUB_STATUS_RESOLUTION &&
                            $detailTicket->report_status === \App\Models\Ticket::REPORT_STATUS_VERIFIED &&
                            $detailTicket->report_is_valid === true)
                        <flux:card class="space-y-4 border-dashed border-green-200 p-4 sm:p-5 dark:border-green-900/40">
                            <flux:heading size="lg">Tutup Tiket</flux:heading>
                            <flux:subheading>Secara normal tiket ditutup oleh responder setelah menandai selesai di halaman penanganan. Gunakan tombol ini jika penutupan manual dari koordinator diperlukan.</flux:subheading>
                            <div class="flex flex-wrap justify-end gap-2">
                                <flux:button type="button" variant="danger"
                                    @click="openConfirm({
                                        title: 'Tutup Tiket?',
                                        message: 'Tiket akan ditutup. Lanjutkan?',
                                        variant: 'danger',
                                        action: async () => { await $wire.closeTicketByCoordinator(); }
                                    })"
                                    wire:loading.attr="disabled">
                                    <span wire:loading.remove wire:target="closeTicketByCoordinator">Tutup Tiket</span>
                                    <span wire:loading wire:target="closeTicketByCoordinator">Memproses…</span>
                                </flux:button>
                            </div>
                        </flux:card>
                    @endif
                @endcan

                @can('reopenClosed', $detailTicket)
                    <flux:card class="space-y-4 border-dashed border-red-200 p-4 sm:p-5 dark:border-red-900/40">
                        <flux:heading size="lg">Buka Kembali Tiket (Closed)</flux:heading>
                        <flux:subheading>Masukkan alasan reopen tiket (wajib, minimal 15 karakter). Alasan akan tercatat pada
                            audit.</flux:subheading>
                        <flux:textarea wire:model="reopenReason" rows="4" label="Alasan Reopen"
                            placeholder="Jelaskan mengapa tiket dibuka kembali untuk penanganan lanjutan." />
                        @error('reopenReason')
                            <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                        @enderror
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button type="button" variant="primary"
                                @click="openConfirm({
                                    title: 'Buka Kembali Tiket?',
                                    message: 'Tiket akan dibuka kembali untuk fase Response. Lanjutkan?',
                                    variant: 'primary',
                                    action: async () => { await $wire.reopenClosedByCoordinator(); }
                                })"
                                wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="reopenClosedByCoordinator">Buka Kembali</span>
                                <span wire:loading wire:target="reopenClosedByCoordinator">Memproses…</span>
                            </flux:button>
                        </div>
                    </flux:card>
                @endcan

                @can('manageIncidentReport', $detailTicket)
                    <flux:card class="space-y-3 border-dashed border-zinc-200 p-4 sm:p-5 dark:border-zinc-700/50">
                        <flux:heading size="lg">Laporan Penanganan Insiden</flux:heading>
                        <flux:subheading>Kelola dan simpan draft laporan, lalu lakukan export print-only.</flux:subheading>
                        <div class="flex flex-wrap justify-end gap-2">
                            <flux:button type="button"
                                href="{{ route('tickets.reports.edit', ['ticket' => $detailTicket]) }}"
                                variant="primary" wire:navigate>
                                Buat Laporan
                            </flux:button>
                        </div>
                    </flux:card>
                @endcan
            </div>
        @endif
    </flux:modal>
</div>

<style>
    ui-modal::backdrop,
    dialog::backdrop {
        background-color: rgba(24, 24, 27, 0.5) !important;
    }

    dialog[data-modal="ticket-create-pic-modal"] {
        max-width: 900px !important;
        width: 90vw !important;
    }

    dialog[data-modal="ticket-detail-modal"] {
        max-width: 1200px !important;
        width: 96vw !important;
        overflow-x: hidden !important;
    }

    .confirm-open> :not(.confirm-overlay) {
        filter: blur(2px) brightness(0.45);
        pointer-events: none;
        user-select: none;
    }
</style>
