<div class="space-y-6">
    @if (session()->has('toast_success'))
        <div
            popover="manual"
            x-data="{
                open: true,
                totalMs: 5000,
                remainingMs: 5000,
                tickMs: 50,
                timer: null,
                fadeOpacity() {
                    return Math.max(0, this.remainingMs / this.totalMs);
                },
                close() {
                    this.open = false;
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    if (typeof this.$el.hidePopover === 'function') {
                        this.$el.hidePopover();
                    }
                },
                start() {
                    if (this.timer) {
                        clearInterval(this.timer);
                        this.timer = null;
                    }
                    this.remainingMs = this.totalMs;
                    this.timer = setInterval(() => {
                        this.remainingMs -= this.tickMs;
                        if (this.remainingMs <= 0) {
                            this.remainingMs = 0;
                            this.close();
                        }
                    }, this.tickMs);
                },
            }"
            x-init="$nextTick(() => { if (typeof $el.showPopover === 'function') { $el.showPopover(); } start(); })"
            x-on:livewire:navigating.window="close()"
            x-show="open"
            x-cloak
            :style="{ opacity: fadeOpacity() }"
            class="app-alert-popover pointer-events-auto rounded-lg border border-border-strong bg-success px-4 py-3 text-sm text-success-foreground shadow-lg"
            role="status"
        >
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click.stop.prevent="close()"
                    class="text-base leading-none text-success-foreground/90 hover:text-success-foreground"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Laporan Koordinator</flux:heading>
            <p class="mt-1 font-mono text-sm text-muted-foreground">
                {{ $ticket->ticket_number ?? '—' }} — {{ $ticket->title ?? '—' }}
            </p>
        </div>
        <flux:button href="{{ route('tickets.index', ['ticket' => $ticket->public_id]) }}" variant="ghost" wire:navigate>
            Kembali
        </flux:button>
    </div>

    <flux:card class="space-y-6 p-4 sm:p-5">
        <div class="flex flex-wrap items-start justify-between gap-3">
            <div class="space-y-1">
                <flux:heading size="lg">Editor & Preview</flux:heading>
                <flux:subheading>Konten disimpan sebagai HTML yang disanitasi di server.</flux:subheading>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-2">
            <div class="space-y-3">
                <flux:heading size="sm">Editor</flux:heading>
                <div
                    data-ticket-report-editor
                    data-initial-html="{{ $bodyHtml }}"
                    data-upload-url="{{ route('tickets.reports.images.store', ['ticket' => $ticket->public_id]) }}"
                    wire:ignore
                    x-on:ticket-report-editor:change="$wire.set('bodyHtml', $event.detail.html, true)"
                    x-on:ticket-report-editor-reset.window="
                        if ($event.detail.ticketPublicId === '{{ $ticket->public_id }}') {
                            $el.dispatchEvent(new CustomEvent('ticket-report-editor:replace-content', {
                                bubbles: true,
                                detail: { html: $event.detail.html }
                            }))
                        }
                    "
                    class="tiptap-simple ticket-editor-panel space-y-2"
                >
                    <input type="hidden" data-editor-body>
                    <div class="ticket-editor-toolbar flex items-center gap-2 rounded-lg border border-border bg-muted p-2">
                        <select
                            data-editor-node-type
                            class="ticket-editor-select"
                        >
                            <option value="paragraph">Paragraph</option>
                            <option value="heading-1">Heading 1</option>
                            <option value="heading-2">Heading 2</option>
                            <option value="heading-3">Heading 3</option>
                        </select>
                        <div data-editor-toolbar class="flex items-center gap-1"></div>
                        <select
                            data-editor-font-size
                            class="ticket-editor-select"
                        >
                            <option value="">Font normal</option>
                            <option value="12px">12px</option>
                            <option value="14px">14px</option>
                            <option value="16px">16px</option>
                            <option value="18px">18px</option>
                            <option value="20px">20px</option>
                            <option value="24px">24px</option>
                        </select>
                    </div>
                    @php
                        $imageEvidences = $ticket->evidences->filter(fn ($evidence) => $evidence->isLikelyImage());
                    @endphp
                    @if ($imageEvidences->isNotEmpty())
                        <div class="ticket-editor-evidence-strip rounded-md border border-border bg-muted p-2">
                            <p class="mb-2 text-xs font-semibold text-foreground-secondary">
                                Eviden gambar (drag & drop ke editor atau klik untuk sisipkan)
                            </p>
                            <div class="flex gap-2 overflow-x-auto pb-1">
                                @foreach ($imageEvidences as $evidence)
                                    @php
                                        $evidenceUrl = route('tickets.evidence.show', $evidence);
                                    @endphp
                                    <button
                                        type="button"
                                        draggable="true"
                                        data-evidence-url="{{ $evidenceUrl }}"
                                        data-evidence-insert
                                        class="ticket-editor-evidence-item"
                                        title="Klik atau drag untuk menyisipkan: {{ $evidence->original_name }}"
                                    >
                                        <img
                                            src="{{ $evidenceUrl }}"
                                            alt="{{ $evidence->original_name ?? 'Eviden gambar' }}"
                                            class="h-16 w-24 rounded object-cover"
                                            loading="lazy"
                                        >
                                    </button>
                                @endforeach
                            </div>
                        </div>
                    @endif
                    <div data-editor-content class="ticket-editor-content min-h-[20rem] rounded-lg border border-border bg-surface p-3"></div>
                </div>
                @error('bodyHtml')
                    <p class="text-sm font-medium text-danger">{{ $message }}</p>
                @enderror

                <div class="flex flex-wrap items-center justify-end gap-2 pt-2">
                    <flux:button type="button" variant="ghost" wire:click="regenerateFromSnapshot" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="regenerateFromSnapshot">Muat Ulang</span>
                        <span wire:loading wire:target="regenerateFromSnapshot">Memproses…</span>
                    </flux:button>

                    <flux:button type="button" variant="primary" wire:click="saveDraft" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveDraft">Simpan Draft</span>
                        <span wire:loading wire:target="saveDraft">Menyimpan…</span>
                    </flux:button>

                    <flux:button type="button" variant="ghost" wire:click="exportPrint" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="exportPrint">Export / Print</span>
                        <span wire:loading wire:target="exportPrint">Mempersiapkan…</span>
                    </flux:button>
                </div>
            </div>

            <div class="space-y-3">
                <flux:heading size="sm">Ringkasan Tiket</flux:heading>
                <div class="ticket-summary-panel overflow-auto rounded-lg border border-border bg-muted p-3 text-sm">
                    @php
                        $snapshot = is_array($snapshotJson ?? null) ? $snapshotJson : [];
                        $snapshotTicket = is_array($snapshot['ticket'] ?? null) ? $snapshot['ticket'] : [];
                        $snapshotAnalyses = is_array($snapshot['analyses'] ?? null) ? $snapshot['analyses'] : [];
                        $snapshotResponses = is_array($snapshot['response_actions'] ?? null) ? $snapshot['response_actions'] : [];
                    @endphp

                    <div class="mb-4 space-y-2">
                        <details open class="rounded-md border border-border bg-surface p-2">
                            <summary class="cursor-pointer select-none text-sm font-semibold text-foreground">
                                Ringkasan Laporan Awal
                            </summary>
                            <div class="mt-2 space-y-1 text-sm text-foreground-secondary">
                                <p><span class="font-medium">No. Tiket:</span> {{ $snapshotTicket['ticket_number'] ?? '—' }}</p>
                                <p><span class="font-medium">Judul:</span> {{ $snapshotTicket['title'] ?? '—' }}</p>
                                <p><span class="font-medium">Pelapor:</span> {{ $snapshotTicket['reporter_name'] ?? '—' }}</p>
                                <p><span class="font-medium">Email:</span> {{ $snapshotTicket['reporter_email'] ?? '—' }}</p>
                                <p><span class="font-medium">Kategori:</span> {{ $snapshotTicket['incident_category'] ?? '—' }}</p>
                                <p><span class="font-medium">Keparahan:</span> {{ $snapshotTicket['incident_severity'] ?? '—' }}</p>
                                <p><span class="font-medium">Deskripsi:</span> {{ $snapshotTicket['incident_description'] ?? '—' }}</p>
                            </div>
                        </details>

                        <details class="rounded-md border border-border bg-surface p-2">
                            <summary class="cursor-pointer select-none text-sm font-semibold text-foreground">
                                Ringkasan Analisis ({{ count($snapshotAnalyses) }})
                            </summary>
                            <div class="mt-2 space-y-2 text-sm text-foreground-secondary">
                                @forelse ($snapshotAnalyses as $index => $analysis)
                                    <div class="rounded border border-border p-2">
                                        <p class="font-medium text-foreground">Analisis #{{ $index + 1 }}</p>
                                        <p>Severity: {{ $analysis['severity'] ?? '—' }}</p>
                                        <p>Dampak: {{ $analysis['impact'] ?? '—' }}</p>
                                        <p>Akar masalah: {{ $analysis['root_cause'] ?? '—' }}</p>
                                        <p>Rekomendasi: {{ $analysis['recommendation'] ?? '—' }}</p>
                                    </div>
                                @empty
                                    <p>Belum ada data analisis.</p>
                                @endforelse
                            </div>
                        </details>

                        <details class="rounded-md border border-border bg-surface p-2">
                            <summary class="cursor-pointer select-none text-sm font-semibold text-foreground">
                                Ringkasan Penanganan Insiden ({{ count($snapshotResponses) }})
                            </summary>
                            <div class="mt-2 space-y-2 text-sm text-foreground-secondary">
                                @forelse ($snapshotResponses as $response)
                                    <div class="rounded border border-border p-2">
                                        <p><span class="font-medium">Jenis:</span> {{ $response['action_type'] ?? '—' }}</p>
                                        <p><span class="font-medium">Deskripsi:</span> {{ $response['description'] ?? '—' }}</p>
                                        <p><span class="font-medium">Pelaksana:</span> {{ $response['performed_by'] ?? '—' }}</p>
                                    </div>
                                @empty
                                    <p>Belum ada data penanganan insiden.</p>
                                @endforelse
                            </div>
                        </details>
                    </div>
                </div>
            </div>
        </div>
    </flux:card>

    <flux:card class="p-4 sm:p-5">
        <flux:heading size="sm">Info dokumen</flux:heading>
        <div class="mt-2 text-sm text-foreground-secondary">
            <div><span class="font-medium text-foreground">Status:</span> {{ $ticketReport->status ?? 'draft' }}</div>
            <div class="mt-1"><span class="font-medium text-foreground">Terakhir tersimpan:</span> {{ $ticketReport->updated_at?->format('d M Y H:i') ?? '—' }}</div>
        </div>
    </flux:card>
</div>

