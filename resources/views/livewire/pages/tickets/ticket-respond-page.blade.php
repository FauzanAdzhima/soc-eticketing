<div
    class="space-y-6"
    x-data="{
        confirmTitle: 'Konfirmasi',
        confirmMessage: '',
        confirmVariant: 'primary',
        confirmBusy: false,
        confirmAction: null,
        showConfirm: false,
        onConfirmDialogClose() {
            this.confirmBusy = false;
            this.confirmAction = null;
            this.confirmMessage = '';
            this.confirmTitle = 'Konfirmasi';
            this.showConfirm = false;
        },
        openConfirm({ title = 'Konfirmasi', message = '', variant = 'primary', action = null }) {
            this.confirmTitle = title;
            this.confirmMessage = message;
            this.confirmVariant = variant;
            this.confirmAction = action;
            this.confirmBusy = false;
            this.showConfirm = true;
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
    }"
>
    <div
        x-show="showConfirm"
        x-cloak
        class="fixed inset-0 z-50 flex items-center justify-center bg-scrim/40 backdrop-blur-sm dark:bg-scrim/60"
        @keydown.escape.window="!confirmBusy && closeConfirm()"
    >
        <div class="m-4 w-[min(100vw-2rem,32rem)] max-w-lg rounded-xl border border-border bg-surface p-5 shadow-2xl">
            <div class="flex items-start justify-between gap-3">
                <div class="min-w-0">
                    <p class="text-base font-semibold text-foreground" x-text="confirmTitle"></p>
                    <p class="mt-1 text-sm text-foreground-secondary" x-text="confirmMessage"></p>
                </div>
                <button
                    type="button"
                    class="shrink-0 text-xl leading-none text-muted-foreground hover:text-foreground"
                    aria-label="Tutup"
                    @click="!confirmBusy && closeConfirm()"
                >
                    &times;
                </button>
            </div>
            <div class="mt-5 flex justify-end gap-2">
                <flux:button type="button" variant="ghost" @click="!confirmBusy && closeConfirm()" x-bind:disabled="confirmBusy">
                    Batal
                </flux:button>
                <flux:button
                    type="button"
                    variant="primary"
                    @click="runConfirm()"
                    x-show="confirmVariant !== 'danger'"
                    x-bind:disabled="confirmBusy"
                >
                    <span x-show="!confirmBusy">Konfirmasi</span>
                    <span x-show="confirmBusy">Memproses…</span>
                </flux:button>
                <flux:button
                    type="button"
                    variant="danger"
                    @click="runConfirm()"
                    x-show="confirmVariant === 'danger'"
                    x-bind:disabled="confirmBusy"
                >
                    <span x-show="!confirmBusy">Konfirmasi</span>
                    <span x-show="confirmBusy">Memproses…</span>
                </flux:button>
            </div>
        </div>
    </div>
    @if (session()->has('toast_success'))
        <div x-data="{ open: true }"
            x-init="window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => open = false, 5000)"
            x-show="open"
            class="rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-foreground"
            role="status">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif
    @if (session()->has('toast_error'))
        <div x-data="{ open: true }"
            x-init="window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => open = false, 5000)"
            x-show="open"
            class="rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-foreground"
            role="status">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_error') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <flux:heading size="xl">Penanganan insiden</flux:heading>
            <p class="font-mono text-sm text-muted-foreground">{{ $ticket->ticket_number ?? '—' }} — {{ $ticket->title }}</p>
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $phase['badge_class'] }}">
                {{ $phase['label'] }}
            </span>
        </div>
        <flux:button href="{{ route('tickets.index', auth()->user()?->seesOnlyResponderTicketListInNavigation() ? ['scope' => 'responder'] : []) }}" variant="ghost" wire:navigate>
            Kembali
        </flux:button>
    </div>

    @if (! $canRecord && auth()->user()?->can('ticket.respond'))
        <div class="rounded-lg border border-warning/40 bg-warning/10 px-4 py-3 text-sm text-foreground" role="status">
            <p class="font-medium">Mode baca saja</p>
            @if ($canStartResponse)
                <p class="mt-1 text-foreground-secondary">Tiket masih berada di fase Analysis. Gunakan tombol <strong class="font-semibold">Tangani Tiket</strong> di bawah untuk memindahkan ke fase Response; setelah itu form pencatatan tindakan akan aktif.</p>
            @elseif ($ticket->sub_status === \App\Models\Ticket::SUB_STATUS_RESOLUTION)
                <p class="mt-1 text-foreground-secondary">Fase penanganan ditandai selesai (Resolution). Form pencatatan tindakan dinonaktifkan. Jika perlu menambah catatan, minta <strong class="font-semibold">koordinator</strong> membuka kembali fase respons dari rincian tiket di daftar tiket.</p>
            @else
                <p class="mt-1 text-foreground-secondary">Anda tidak memiliki penugasan aktif pada tiket ini atau sub-status belum mengizinkan pencatatan tindakan. Hubungi koordinator untuk penugasan.</p>
            @endif
        </div>
    @endif

    <flux:card class="space-y-4 p-4 sm:p-5">
        <flux:heading size="lg">Ringkasan Analisis Terbaru</flux:heading>
        @if ($latestAnalysis === null)
            <flux:text class="text-muted-foreground">Tidak ada data analisis.</flux:text>
        @else
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-muted-foreground">Tingkat Keparahan (Analisis)</dt>
                    <dd class="mt-0.5 text-foreground">{{ $latestAnalysis->severity ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-muted-foreground">Sub-Status Tiket</dt>
                    <dd class="mt-0.5 text-foreground">{{ $ticket->sub_status ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-muted-foreground">Dampak</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ filled($latestAnalysis->impact) ? $latestAnalysis->impact : '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-muted-foreground">Rekomendasi</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ filled($latestAnalysis->recommendation) ? $latestAnalysis->recommendation : '—' }}</dd>
                </div>
            </dl>
            @if ($latestAnalysis->iocs->isNotEmpty())
                <div>
                    <p class="text-sm font-medium text-foreground-secondary">IOC (Entri Terbaru)</p>
                    <ul class="mt-2 divide-y divide-border rounded-lg border border-border">
                        @foreach ($latestAnalysis->iocs as $ioc)
                            <li class="px-3 py-2 text-sm" wire:key="resp-ioc-{{ $ioc->id }}">
                                <span class="font-medium text-foreground">{{ $ioc->iocType?->ioc_type ?? 'IOC' }}</span>
                                <span class="mx-1 text-muted-foreground">·</span>
                                <code class="rounded bg-muted px-1 py-0.5 text-xs text-foreground">{{ $ioc->value }}</code>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </flux:card>

    @can('ticket.chat.view')
        <section id="diskusi-tiket" class="scroll-mt-6" aria-labelledby="diskusi-tiket-heading">
            <flux:card class="overflow-hidden p-0">
                <details class="group border-0">
                    <summary
                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-muted/80 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary [&::-webkit-details-marker]:hidden"
                    >
                        <div class="min-w-0 flex-1">
                            <span class="block text-sm font-semibold text-foreground" id="diskusi-tiket-heading">Diskusi / Chat</span>
                            <span class="mt-0.5 block text-xs font-normal text-muted-foreground">Komunikasi dengan pelapor dan catatan tim (internal)</span>
                        </div>
                        <div class="flex shrink-0 items-center gap-2" onclick="event.stopPropagation()">
                            <flux:button type="button" size="sm" variant="ghost" href="{{ route('tickets.chat', $ticket) }}" wire:navigate>
                                Buka layar penuh
                            </flux:button>
                        </div>
                        <svg
                            class="size-5 shrink-0 text-muted-foreground transition-transform duration-200 group-open:rotate-180"
                            xmlns="http://www.w3.org/2000/svg"
                            viewBox="0 0 20 20"
                            fill="currentColor"
                            aria-hidden="true"
                        >
                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                        </svg>
                    </summary>
                    <div class="border-t border-border p-4 sm:p-5">
                        @livewire('ticket.chat', ['ticket' => $ticket], key('ticket-chat-respond-' . $ticket->id))
                    </div>
                </details>
            </flux:card>
        </section>
    @endcan

    <section class="space-y-4" aria-labelledby="timeline-heading">
        <flux:heading size="lg" id="timeline-heading">Linimasa Penanganan</flux:heading>
        @if ($timeline->isEmpty())
            <flux:card class="p-4 sm:p-5">
                <flux:text class="text-muted-foreground">Belum ada tindakan atau perubahan fase yang tercatat di linimasa ini.</flux:text>
            </flux:card>
        @else
            <div class="relative space-y-0 border-l-2 border-border pl-6">
                @foreach ($timeline as $index => $entry)
                    @php
                        $entryKey = 'tl-'.$index.'-'.$entry['type'];
                    @endphp
                    <div class="relative pb-8 last:pb-0" wire:key="{{ $entryKey }}">
                        <span class="absolute -left-[9px] top-1.5 size-4 rounded-full border-2 border-surface bg-info" aria-hidden="true"></span>
                        @if ($entry['type'] === 'action')
                            @php
                                /** @var \App\Models\IncidentResponseAction $act */
                                $act = $entry['payload'];
                                $typeLabel = match ($act->action_type) {
                                    \App\Models\IncidentResponseAction::TYPE_MITIGATION => 'Mitigasi',
                                    \App\Models\IncidentResponseAction::TYPE_ERADICATION => 'Eradikasi',
                                    \App\Models\IncidentResponseAction::TYPE_RECOVERY => 'Pemulihan',
                                    default => $act->action_type,
                                };
                            @endphp
                            <flux:card class="overflow-hidden p-0">
                                <details class="group border-0">
                                    <summary
                                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-muted/80 [&::-webkit-details-marker]:hidden"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-foreground">{{ $typeLabel }}</p>
                                            <p class="mt-0.5 text-xs text-muted-foreground">
                                                {{ $act->performer?->name ?? 'User #'.$act->performed_by }}
                                                · {{ $act->created_at?->format('d M Y H:i') ?? '—' }}
                                            </p>
                                        </div>
                                        <svg class="size-5 shrink-0 text-muted-foreground transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </summary>
                                    <div class="space-y-3 border-t border-border px-4 py-4 text-sm">
                                        <p class="whitespace-pre-wrap text-foreground">{{ $act->description }}</p>
                                        @if (filled($act->meta))
                                            <pre class="max-h-40 overflow-auto rounded bg-muted p-3 text-xs text-foreground">{{ json_encode($act->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                                        @endif
                                    </div>
                                </details>
                            </flux:card>
                        @elseif ($entry['type'] === 'log_resolved')
                            @php
                                /** @var \App\Models\TicketLog $log */
                                $log = $entry['payload'];
                            @endphp
                            <flux:card class="p-4 sm:p-5">
                                <p class="text-sm font-semibold text-success">Penanganan ditandai selesai</p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ $log->user?->name ?? 'Sistem' }} · {{ $log->created_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </flux:card>
                        @else
                            @php
                                /** @var \App\Models\TicketLog $log */
                                $log = $entry['payload'];
                                $data = json_decode((string) $log->data, true) ?: [];
                            @endphp
                            <flux:card class="p-4 sm:p-5">
                                <p class="text-sm font-semibold text-foreground">Perubahan Sub-Status</p>
                                <p class="mt-1 text-xs text-foreground-secondary">
                                    {{ ($data['from'] ?? '—') }} → {{ ($data['to'] ?? '—') }}
                                </p>
                                <p class="mt-1 text-xs text-muted-foreground">
                                    {{ $log->user?->name ?? '—' }} · {{ $log->created_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </flux:card>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </section>

    @if ($canStartResponse)
        <flux:card class="space-y-3 p-4 sm:p-5">
            <flux:heading size="lg">Mulai Fase Respons</flux:heading>
            <flux:text class="text-sm text-foreground-secondary">Setelah Anda memulai, sub-status tiket menjadi Response dan Anda dapat mencatat tindakan mitigasi, eradikasi, atau pemulihan.</flux:text>
            <div class="flex justify-end">
                <flux:button type="button" variant="primary" wire:click="startResponseHandling" wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="startResponseHandling">Tangani Tiket</span>
                    <span wire:loading wire:target="startResponseHandling">Memproses…</span>
                </flux:button>
            </div>
        </flux:card>
    @endif

    @if ($canRecord)
        <form wire:submit="saveAction" class="space-y-6">
            <flux:card class="space-y-4 p-4 sm:p-5">
                <flux:heading size="lg">Catat tindakan</flux:heading>
                <flux:select label="Jenis tindakan" wire:model="actionType">
                    <flux:select.option value="{{ \App\Models\IncidentResponseAction::TYPE_MITIGATION }}">Mitigasi</flux:select.option>
                    <flux:select.option value="{{ \App\Models\IncidentResponseAction::TYPE_ERADICATION }}">Eradikasi</flux:select.option>
                    <flux:select.option value="{{ \App\Models\IncidentResponseAction::TYPE_RECOVERY }}">Pemulihan</flux:select.option>
                </flux:select>
                @error('actionType')
                    <p class="text-sm font-medium text-danger">{{ $message }}</p>
                @enderror
                <flux:textarea label="Deskripsi" wire:model="description" rows="5" placeholder="Uraian tindakan yang dilakukan (wajib)." />
                @error('description')
                    <p class="text-sm font-medium text-danger">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveAction">Simpan Tindakan</span>
                        <span wire:loading wire:target="saveAction">Menyimpan…</span>
                    </flux:button>
                </div>
            </flux:card>
        </form>
    @endif

    @if ($canMarkResolved)
        <flux:card class="border-success/40 p-4 sm:p-5 dark:border-success/30">
            <flux:heading size="lg">Selesaikan Fase Respons</flux:heading>
            <flux:text class="mt-1 text-sm text-foreground-secondary">Sub-status tiket diubah menjadi Resolution setelah tindakan mencukupi. Penutupan tiket tetap oleh koordinator.</flux:text>
            <div class="mt-4 flex justify-end">
                <flux:button
                    type="button"
                    variant="primary"
                    @click="openConfirm({
                        title: 'Tandai Selesai?',
                        message: 'Tandai penanganan respons selesai dan ubah sub-status ke Resolution?',
                        variant: 'primary',
                        action: async () => { await $wire.markResolved(); }
                    })"
                    wire:loading.attr="disabled"
                >
                    <span wire:loading.remove wire:target="markResolved">Tandai Selesai</span>
                    <span wire:loading wire:target="markResolved">Memproses…</span>
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>
