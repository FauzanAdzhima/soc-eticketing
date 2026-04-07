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
                    <div class="ticket-editor-sticky-tools">
                        {{-- Accordion: Toolbar --}}
                        <details open class="ticket-editor-accordion">
                            <summary class="ticket-editor-accordion-summary">
                                <svg class="ticket-editor-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                Toolbar
                            </summary>
                            <div class="ticket-editor-toolbar flex items-center gap-2 p-2">
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
                        </details>

                        {{-- Accordion: Eviden gambar --}}
                        @php
                            $imageEvidences = $ticket->evidences->filter(fn ($evidence) => $evidence->isLikelyImage());
                        @endphp
                        @if ($imageEvidences->isNotEmpty())
                            <details class="ticket-editor-accordion">
                                <summary class="ticket-editor-accordion-summary">
                                    <svg class="ticket-editor-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    Eviden gambar ({{ $imageEvidences->count() }})
                                </summary>
                                <div class="p-2">
                                    <p class="mb-2 text-xs text-muted-foreground">Drag & drop ke editor atau klik untuk sisipkan</p>
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
                            </details>
                        @endif

                        {{-- Accordion: Lampiran dari chat --}}
                        @php
                            $imageChatAttachments = $chatAttachments->filter(fn ($msg) => $msg->attachmentPreviewKind() === 'image');
                            $fileChatAttachments = $chatAttachments->filter(fn ($msg) => $msg->attachmentPreviewKind() !== 'image');
                        @endphp
                        @if ($chatAttachments->isNotEmpty())
                            <details class="ticket-editor-accordion">
                                <summary class="ticket-editor-accordion-summary">
                                    <svg class="ticket-editor-accordion-chevron" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor"><path fill-rule="evenodd" d="M5.22 8.22a.75.75 0 0 1 1.06 0L10 11.94l3.72-3.72a.75.75 0 1 1 1.06 1.06l-4.25 4.25a.75.75 0 0 1-1.06 0L5.22 9.28a.75.75 0 0 1 0-1.06Z" clip-rule="evenodd"/></svg>
                                    Lampiran dari chat ({{ $chatAttachments->count() }})
                                </summary>
                                <div class="p-2">
                                    <p class="mb-2 text-xs text-muted-foreground">Drag & drop ke editor atau klik untuk sisipkan</p>
                                    @if ($imageChatAttachments->isNotEmpty())
                                        <div class="flex gap-2 overflow-x-auto pb-1">
                                            @foreach ($imageChatAttachments as $msg)
                                                @php
                                                    $chatAttUrl = route('tickets.chat.attachment.show', [
                                                        'ticket' => $ticket->public_id,
                                                        'message' => $msg->id,
                                                    ]);
                                                @endphp
                                                <button
                                                    type="button"
                                                    draggable="true"
                                                    data-evidence-url="{{ $chatAttUrl }}"
                                                    data-evidence-insert
                                                    class="ticket-editor-evidence-item"
                                                    title="Chat: {{ $msg->attachment_original_name }} ({{ $msg->user?->name ?? $msg->guest_name ?? 'Pelapor' }}, {{ $msg->created_at?->format('d M H:i') }})"
                                                >
                                                    <img
                                                        src="{{ $chatAttUrl }}"
                                                        alt="{{ $msg->attachment_original_name ?? 'Lampiran chat' }}"
                                                        class="h-16 w-24 rounded object-cover"
                                                        loading="lazy"
                                                    >
                                                </button>
                                            @endforeach
                                        </div>
                                    @endif
                                    @if ($fileChatAttachments->isNotEmpty())
                                        <div class="mt-1 flex flex-wrap gap-1.5">
                                            @foreach ($fileChatAttachments as $msg)
                                                @php
                                                    $chatFileUrl = route('tickets.chat.attachment.show', [
                                                        'ticket' => $ticket->public_id,
                                                        'message' => $msg->id,
                                                    ]);
                                                @endphp
                                                <a href="{{ $chatFileUrl }}" target="_blank" rel="noopener"
                                                    class="inline-flex items-center gap-1 rounded-md border border-border bg-surface px-2 py-1 text-xs text-foreground transition hover:bg-muted"
                                                    title="{{ $msg->user?->name ?? $msg->guest_name ?? 'Pelapor' }}, {{ $msg->created_at?->format('d M H:i') }}">
                                                    <svg class="h-3.5 w-3.5 shrink-0 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                        <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                    </svg>
                                                    <span class="max-w-[10rem] truncate">{{ $msg->attachment_original_name ?? 'Lampiran' }}</span>
                                                </a>
                                            @endforeach
                                        </div>
                                    @endif
                                </div>
                            </details>
                        @endif
                    </div>
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

                        @if ($chatAttachments->isNotEmpty())
                            <details class="rounded-md border border-border bg-surface p-2">
                                <summary class="cursor-pointer select-none text-sm font-semibold text-foreground">
                                    Lampiran dari Chat ({{ $chatAttachments->count() }})
                                </summary>
                                <div class="mt-2 space-y-1.5 text-sm text-foreground-secondary">
                                    @foreach ($chatAttachments as $msg)
                                        <div class="flex items-start gap-2 rounded border border-border p-1.5">
                                            @if ($msg->attachmentPreviewKind() === 'image')
                                                <img
                                                    src="{{ route('tickets.chat.attachment.show', ['ticket' => $ticket->public_id, 'message' => $msg->id]) }}"
                                                    alt="{{ $msg->attachment_original_name }}"
                                                    class="h-10 w-14 rounded object-cover"
                                                    loading="lazy"
                                                >
                                            @else
                                                <svg class="mt-0.5 h-5 w-5 shrink-0 text-muted-foreground" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                                                    <path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                                </svg>
                                            @endif
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate font-medium text-foreground">{{ $msg->attachment_original_name ?? 'Lampiran' }}</p>
                                                <p class="text-xs">{{ $msg->user?->name ?? $msg->guest_name ?? 'Pelapor' }} &middot; {{ $msg->created_at?->format('d M H:i') }}</p>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </details>
                        @endif
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

