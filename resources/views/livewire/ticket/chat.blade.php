@php
    /** @var \Illuminate\Support\Collection<int, \App\Models\TicketMessage> $chatMessages */
    $authId = auth()->id();
    // Tamu: UI chat berbahasa Indonesia (hardcoded); paksa locale id agar status/sub-status selaras.
    // Staff: ikuti locale aplikasi (APP_LOCALE / session).
    $ticketStatusTransLocale = $isGuest ? 'id' : null;
    $ticketStatusLabels = trans('tickets.coordinator_badge_labels', [], $ticketStatusTransLocale);
    $ticketStatusDisplay =
        is_array($ticketStatusLabels) && isset($ticketStatusLabels[$ticket->status])
            ? $ticketStatusLabels[$ticket->status]
            : (string) $ticket->status;
    $subStatusMap = trans('tickets.sub_status_labels', [], $ticketStatusTransLocale);
    $ticketSubStatusDisplay = null;
    if (filled($ticket->sub_status)) {
        $ticketSubStatusDisplay =
            is_array($subStatusMap) && isset($subStatusMap[$ticket->sub_status])
                ? $subStatusMap[$ticket->sub_status]
                : (string) $ticket->sub_status;
    }

    $ticketStatusBadgeColor = match ($ticket->status) {
        \App\Models\Ticket::STATUS_AWAITING_VERIFICATION => 'amber',
        \App\Models\Ticket::STATUS_OPEN => 'sky',
        \App\Models\Ticket::STATUS_ON_PROGRESS => 'blue',
        \App\Models\Ticket::STATUS_CLOSED => 'emerald',
        \App\Models\Ticket::STATUS_REPORT_REJECTED => 'rose',
        default => 'zinc',
    };

    $ticketSubStatusBadgeColor = 'zinc';
    if (filled($ticket->sub_status)) {
        $ticketSubStatusBadgeColor = match ($ticket->sub_status) {
            \App\Models\Ticket::SUB_STATUS_TRIAGE => 'amber',
            \App\Models\Ticket::SUB_STATUS_ANALYSIS => 'violet',
            \App\Models\Ticket::SUB_STATUS_RESPONSE => 'cyan',
            \App\Models\Ticket::SUB_STATUS_RESOLUTION => 'lime',
            default => 'zinc',
        };
    }
@endphp

<div class="flex flex-col gap-3 rounded-xl border border-zinc-200 bg-zinc-50/80 dark:border-zinc-700 dark:bg-zinc-900/40"
    wire:poll.4s data-ticket-chat-root x-data="{
        pinnedToBottom: true,
        scrollToBottom() {
            const el = this.$refs.chatScroll;
            if (el) {
                requestAnimationFrame(() => {
                    el.scrollTop = el.scrollHeight;
                    this.pinnedToBottom = true;
                });
            }
        },
        scrollToBottomIfPinned() {
            const el = this.$refs.chatScroll;
            if (!el) return;
            const threshold = 96;
            const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
            if (this.pinnedToBottom || distance < threshold) {
                this.scrollToBottom();
                clearTimeout(this._readT);
                this._readT = setTimeout(() => $wire.markChatRead(), 250);
            }
        },
        onScroll() {
            const el = this.$refs.chatScroll;
            if (!el) return;
            const threshold = 64;
            const distance = el.scrollHeight - el.scrollTop - el.clientHeight;
            this.pinnedToBottom = distance < threshold;
            if (this.pinnedToBottom) {
                clearTimeout(this._readT);
                this._readT = setTimeout(() => $wire.markChatRead(), 250);
            }
        },
        maybeMarkReadIfShort() {
            this.$nextTick(() => {
                const el = this.$refs.chatScroll;
                if (el && el.scrollHeight <= el.clientHeight + 12) {
                    $wire.markChatRead();
                }
            });
        },
        init() {
            this.scrollToBottom();
            this.maybeMarkReadIfShort();
            const el = this.$refs.chatScroll;
            if (!el) return;
            const obs = new MutationObserver(() => {
                this.scrollToBottomIfPinned();
                this.maybeMarkReadIfShort();
            });
            obs.observe(el, { childList: true, subtree: true });
        },
    }" x-init="init()"
    @ticket-chat-scroll-bottom.window="scrollToBottom()">
    <div class="border-b border-zinc-200 px-3 py-2 dark:border-zinc-700">
        <h3 class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
            @if ($isGuest)
                Diskusi dengan Petugas Tiket
            @else
                Chat tiket
            @endif
        </h3>
        <div class="mt-1.5 flex flex-wrap items-center gap-x-2 gap-y-1">
            <span
                class="text-[0.65rem] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ trans('tickets.chat_field_status', [], $ticketStatusTransLocale) }}</span>
            <flux:badge size="sm" color="{{ $ticketStatusBadgeColor }}">{{ $ticketStatusDisplay }}</flux:badge>
            @if ($ticketSubStatusDisplay !== null)
                <span class="text-zinc-300 dark:text-zinc-600" aria-hidden="true">·</span>
                <span
                    class="text-[0.65rem] font-medium uppercase tracking-wide text-zinc-500 dark:text-zinc-400">{{ trans('tickets.chat_field_sub_status', [], $ticketStatusTransLocale) }}</span>
                <flux:badge size="sm" color="{{ $ticketSubStatusBadgeColor }}">{{ $ticketSubStatusDisplay }}
                </flux:badge>
            @endif
        </div>
        @if (!$isGuest && $this->canSendInternal())
            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                Pesan <span class="font-medium">internal</span> hanya untuk tim; pelapor tidak melihatnya.
            </p>
        @endif
        @if (!empty($typingIndicator))
            <p class="mt-1.5 flex items-center gap-1.5 text-xs italic text-zinc-500 dark:text-zinc-400"
                wire:key="typing-{{ $ticket->id }}">
                <span class="inline-flex gap-0.5" aria-hidden="true">
                    <span
                        class="h-1 w-1 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.2s] dark:bg-zinc-500"></span>
                    <span
                        class="h-1 w-1 animate-bounce rounded-full bg-zinc-400 [animation-delay:-0.1s] dark:bg-zinc-500"></span>
                    <span class="h-1 w-1 animate-bounce rounded-full bg-zinc-400 dark:bg-zinc-500"></span>
                </span>
                {{ $typingIndicator }}
            </p>
        @endif
    </div>

    <div class="ticket-chat-scroll max-h-80 min-h-[12rem] space-y-3 overflow-y-auto px-3 pb-1" x-ref="chatScroll"
        data-chat-scroll wire:key="chat-scroll-{{ $ticket->id }}" @scroll.passive="onScroll">
        @forelse ($chatMessages as $msg)
            @php
                $isStaffSender = $msg->user_id !== null;
                $isMine = !$isGuest && $authId && (int) $msg->user_id === (int) $authId;
                $isInternal = $msg->visibility === \App\Models\TicketMessage::VISIBILITY_INTERNAL;
                $role = $isStaffSender && $msg->user?->roles->isNotEmpty() ? $msg->user->roles->first()->name : null;
                $roleLabel = match ($role) {
                    'pic' => 'PIC Tiket',
                    'analis' => 'Analis Keamanan',
                    'responder' => 'Responder Insiden',
                    default => $role !== null ? \Illuminate\Support\Str::headline($role) : null,
                };
                $roleColor = $role ? \App\Livewire\Ticket\Chat::roleBadgeColor($role) : 'zinc';
                $previewKind = $msg->attachmentPreviewKind();
            @endphp
            @if ($firstUnreadMessageId !== null && (int) $msg->id === (int) $firstUnreadMessageId)
                <div class="relative flex items-center gap-3 py-1"
                    wire:key="unread-{{ $ticket->id }}-{{ $msg->id }}">
                    <div class="h-px flex-1 bg-sky-400/80 dark:bg-sky-500/60"></div>
                    <span
                        class="shrink-0 rounded-full bg-sky-100 px-2 py-0.5 text-[0.65rem] font-semibold uppercase tracking-wide text-sky-800 dark:bg-sky-950/80 dark:text-sky-200">{{ __('Baru') }}</span>
                    <div class="h-px flex-1 bg-sky-400/80 dark:bg-sky-500/60"></div>
                </div>
            @endif
            <div class="flex {{ $isMine ? 'justify-end' : 'justify-start' }}" wire:key="tm-{{ $msg->id }}">
                <div
                    class="max-w-[min(100%,28rem)] rounded-2xl px-3 py-2 text-sm shadow-sm
                        @if ($isInternal) border border-dashed border-amber-300/90 bg-amber-50 text-amber-950 dark:border-amber-700/70 dark:bg-amber-950/35 dark:text-amber-100
                        @elseif ($isMine)
                            bg-sky-600 text-white dark:bg-sky-700
                        @else
                            border border-zinc-200 bg-white text-zinc-900 dark:border-zinc-600 dark:bg-zinc-800 dark:text-zinc-100 @endif
                    ">
                    <div class="mb-1 flex flex-wrap items-center gap-1.5">
                        @if ($isInternal)
                            <flux:badge size="sm" color="amber">{{ __('Tim') }}</flux:badge>
                        @endif
                        @if ($isStaffSender)
                            @if ($isGuest)
                                @if ($roleLabel !== null)
                                    <flux:badge size="sm" color="{{ $roleColor }}">{{ $roleLabel }}
                                    </flux:badge>
                                @else
                                    <span class="font-medium">{{ __('Tim') }}</span>
                                @endif
                            @else
                                <span class="font-medium">{{ $msg->user?->name ?? __('Pengguna') }}</span>
                                @if ($roleLabel !== null)
                                    <flux:badge size="sm" color="{{ $roleColor }}">{{ $roleLabel }}
                                    </flux:badge>
                                @endif
                            @endif
                        @else
                            <span class="font-medium">{{ $msg->guest_name ?? __('Pelapor') }}</span>
                            <flux:badge size="sm" color="sky">{{ __('Pelapor') }}</flux:badge>
                        @endif
                        <span
                            class="text-[0.7rem] opacity-70">{{ $msg->created_at?->timezone(config('app.timezone'))->format('d M H:i') }}</span>
                    </div>
                    <p class="whitespace-pre-wrap break-words">{{ $msg->message }}</p>
                    @if ($msg->attachment_path)
                        @php
                            $attUrl = $this->attachmentUrl($msg);
                        @endphp
                        @if ($previewKind === 'image')
                            <div class="mt-2 overflow-hidden rounded-lg border border-black/10 dark:border-white/10">
                                <a href="{{ $attUrl }}" target="_blank" rel="noopener noreferrer" class="block">
                                    <img src="{{ $attUrl }}"
                                        alt="{{ $msg->attachment_original_name ?? __('Lampiran gambar') }}"
                                        class="max-h-48 w-full object-contain bg-zinc-100 dark:bg-zinc-900"
                                        loading="lazy" />
                                </a>
                            </div>
                            <div class="mt-1 text-xs">
                                <a href="{{ $attUrl }}?disposition=attachment" download
                                    class="font-medium underline opacity-90 hover:opacity-100">{{ __('Unduh') }} —
                                    {{ $msg->attachment_original_name ?? __('Lampiran') }}</a>
                            </div>
                        @elseif ($previewKind === 'pdf')
                            <div class="mt-2 space-y-1">
                                <iframe src="{{ $attUrl }}"
                                    title="{{ $msg->attachment_original_name ?? __('Pratinjau PDF') }}"
                                    class="h-52 w-full rounded-lg border border-black/10 bg-white dark:border-white/10 dark:bg-zinc-900"
                                    loading="lazy"></iframe>
                                <div class="text-xs">
                                    <a href="{{ $attUrl }}?disposition=attachment"
                                        class="font-medium underline opacity-90 hover:opacity-100">{{ __('Unduh PDF') }}</a>
                                </div>
                            </div>
                        @else
                            <div class="mt-2 text-xs">
                                <a href="{{ $attUrl }}" target="_blank" rel="noopener noreferrer"
                                    class="font-medium underline opacity-90 hover:opacity-100">{{ $msg->attachment_original_name ?? __('Lampiran') }}</a>
                            </div>
                        @endif
                    @endif
                </div>
            </div>
        @empty
            <p class="py-6 text-center text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Belum ada pesan.') }}
            </p>
        @endforelse
    </div>

    @if ($ticket->isTerminal())
        <div class="border-t border-zinc-200 px-3 py-3 dark:border-zinc-700">
            <div
                class="flex items-center gap-2 rounded-lg border border-zinc-200 bg-zinc-100 px-3 py-2.5 text-sm text-zinc-500 dark:border-zinc-700 dark:bg-zinc-800 dark:text-zinc-400">
                <svg class="h-4 w-4 shrink-0" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                    stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round"
                        d="M16.5 10.5V6.75a4.5 4.5 0 1 0-9 0v3.75m-.75 11.25h10.5a2.25 2.25 0 0 0 2.25-2.25v-6.75a2.25 2.25 0 0 0-2.25-2.25H6.75a2.25 2.25 0 0 0-2.25 2.25v6.75a2.25 2.25 0 0 0 2.25 2.25Z" />
                </svg>
                <span>{{ __('Tiket sudah ditutup. Pesan baru tidak dapat dikirim sampai tiket dibuka kembali.') }}</span>
            </div>
        </div>
    @elseif ($this->canUseChat())
        <form wire:submit="sendMessage" class="space-y-3 border-t border-zinc-200 p-3 dark:border-zinc-700">
            @if (!$isGuest && $this->canSendExternal() && $this->canSendInternal())
                <flux:radio.group wire:model.live="visibility" label="{{ __('Visibilitas') }}">
                    <flux:radio value="external" label="{{ __('Ke pelapor (eksternal)') }}" />
                    <flux:radio value="internal" label="{{ __('Catatan tim (internal)') }}" />
                </flux:radio.group>
            @elseif (!$isGuest && !$this->canSendExternal() && $this->canSendInternal())
                <p class="text-xs text-zinc-500 dark:text-zinc-400">
                    {{ __('Mengirim sebagai catatan tim (internal).') }}</p>
            @endif

            <flux:textarea wire:model.live.debounce.500ms="body" rows="3" label="{{ __('Pesan') }}"
                placeholder="{{ __('Tulis pesan…') }}" required />
            @error('body')
                <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <div class="space-y-3 border-t border-border pt-3" wire:key="chat-attachment-upload-{{ $ticket->id }}"
                data-ticket-chat-attachment-root
                x-data="{
                    uploading: false,
                    progress: 0,
                    async openChatAttachmentPreview(url, name, kind) {
                        const safeUrl = String(url || '');
                        const safeName = String(name || 'File');
                        if (kind === 'pdf' || kind === 'other') {
                            window.open(safeUrl, '_blank', 'noopener');
                            return;
                        }
                        const previewWindow = window.open('about:blank', '_blank');
                        if (!previewWindow) return;
                        previewWindow.document.title = safeName;
                        previewWindow.document.documentElement.lang = 'id';
                        previewWindow.document.body.style.margin = '0';
                        previewWindow.document.body.style.background = '#0b1020';
                        previewWindow.document.body.style.minHeight = '100vh';
                        previewWindow.document.body.style.display = 'flex';
                        previewWindow.document.body.style.alignItems = 'center';
                        previewWindow.document.body.style.justifyContent = 'center';
                        const img = previewWindow.document.createElement('img');
                        img.alt = safeName;
                        img.style.maxWidth = '100vw';
                        img.style.maxHeight = '100vh';
                        img.style.objectFit = 'contain';
                        previewWindow.document.body.appendChild(img);
                        try {
                            const response = await fetch(safeUrl, { credentials: 'include' });
                            if (!response.ok) throw new Error('Preview fetch failed');
                            const blob = await response.blob();
                            img.src = URL.createObjectURL(blob);
                        } catch (e) {
                            previewWindow.document.body.innerHTML = '';
                            const fallback = previewWindow.document.createElement('a');
                            fallback.href = safeUrl;
                            fallback.target = '_self';
                            fallback.textContent = 'Gagal memuat preview otomatis. Klik untuk membuka file.';
                            fallback.style.color = '#fff';
                            fallback.style.fontFamily = 'sans-serif';
                            fallback.style.fontSize = '14px';
                            previewWindow.document.body.appendChild(fallback);
                        }
                    }
                }"
                x-on:livewire-upload-start="if ($event.detail.property !== 'attachment') return; uploading = true; progress = 0"
                x-on:livewire-upload-finish="if ($event.detail.property !== 'attachment') return; uploading = false; progress = 100"
                x-on:livewire-upload-error="if ($event.detail.property !== 'attachment') return; uploading = false"
                x-on:livewire-upload-progress="if (!uploading) return; progress = $event.detail.progress">
                <flux:label>{{ __('Lampiran (opsional)') }}</flux:label>
                <label
                    class="flex cursor-pointer items-center justify-between gap-3 rounded-lg border border-border px-4 py-3 text-sm text-foreground-secondary transition hover:border-border-strong hover:bg-muted/80">
                    <span class="truncate">{{ __('JPG, PNG, PDF, Office, ZIP/RAR hingga 5 MB — drop atau klik') }}</span>
                    <input type="file" wire:model="attachment" class="hidden"
                        accept=".jpg,.jpeg,.png,.gif,.webp,.pdf,.doc,.docx,.xls,.xlsx,.csv,.txt,.zip,.rar,image/*,application/pdf" />
                    <span class="shrink-0 rounded-md bg-muted px-3 py-1 text-xs font-medium text-foreground">
                        {{ __('Pilih file') }}
                    </span>
                </label>
                <div x-show="uploading" x-cloak class="space-y-1">
                    <div class="h-2 overflow-hidden rounded-full bg-muted">
                        <div class="h-full bg-primary transition-all duration-150" :style="`width: ${progress}%`"></div>
                    </div>
                    <p class="text-xs text-primary">
                        {{ __('Mengunggah file…') }} <span x-text="`${progress}%`"></span>
                    </p>
                </div>
                @error('attachment')
                    <span class="text-xs font-medium text-danger">{{ $message }}</span>
                @enderror

                @if ($attachment)
                    @php
                        $composerName = $attachment->getClientOriginalName();
                        $composerSize = $attachment->getSize();
                        $composerSizeLabel =
                            $composerSize > 0 ? number_format($composerSize / 1024, 1, ',', ' ') . ' KB' : null;
                        $composerMime = $attachment->getMimeType();
                        $composerMimeLower = strtolower((string) $composerMime);
                        $composerNameLower = strtolower($composerName);
                        $composerIsImage =
                            $attachment->isPreviewable() && str_starts_with($composerMimeLower, 'image/');
                        $composerIsPdf =
                            $attachment->isPreviewable() &&
                            (str_contains($composerMimeLower, 'pdf') || str_ends_with($composerNameLower, '.pdf'));
                        $previewKind = $composerIsImage ? 'image' : ($composerIsPdf ? 'pdf' : 'other');
                    @endphp
                    <div class="mt-3 flex flex-col gap-2">
                        <div wire:key="chat-attachment-preview-{{ $ticket->id }}" role="button" tabindex="0"
                            class="relative flex w-full items-center gap-3 rounded-lg border border-border p-2 pr-10 text-left transition hover:bg-muted/80"
                            @click="openChatAttachmentPreview(@js($attachment->temporaryUrl()), @js($composerName), @js($previewKind))"
                            @keydown.enter.prevent="openChatAttachmentPreview(@js($attachment->temporaryUrl()), @js($composerName), @js($previewKind))"
                            @keydown.space.prevent="openChatAttachmentPreview(@js($attachment->temporaryUrl()), @js($composerName), @js($previewKind))">
                            @if ($composerIsImage)
                                <span class="block shrink-0">
                                    <img src="{{ $attachment->temporaryUrl() }}"
                                        alt="{{ __('Pratinjau lampiran') }}"
                                        class="h-12 w-12 rounded object-cover ring-1 ring-border">
                                </span>
                            @else
                                <span
                                    class="flex h-12 w-12 shrink-0 items-center justify-center rounded bg-muted text-[0.65rem] font-semibold uppercase text-muted-foreground ring-1 ring-border"
                                    aria-hidden="true">
                                    {{ $composerIsPdf ? 'PDF' : 'FILE' }}
                                </span>
                            @endif
                            <div class="min-w-0 flex-1">
                                <p class="truncate text-sm font-medium text-foreground" title="{{ $composerName }}">
                                    {{ $composerName }}</p>
                                <p class="text-xs text-muted-foreground">
                                    @if ($composerSizeLabel !== null)
                                        {{ $composerSizeLabel }}
                                    @else
                                        —
                                    @endif
                                </p>
                            </div>
                            <button type="button"
                                class="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-muted-foreground transition hover:bg-danger/10 hover:text-danger"
                                wire:click.stop="removeComposerAttachment" wire:loading.attr="disabled"
                                aria-label="{{ __('Hapus lampiran') }}">
                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                    <path
                                        d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06L11.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 1 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                                </svg>
                            </button>
                        </div>
                    </div>
                @endif
            </div>

            <flux:button type="submit" variant="primary" size="sm" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="sendMessage">{{ __('Kirim') }}</span>
                <span wire:loading wire:target="sendMessage">{{ __('Mengirim…') }}</span>
            </flux:button>
        </form>
    @elseif (!$isGuest)
        <p class="border-t border-zinc-200 px-3 py-3 text-sm text-zinc-500 dark:border-zinc-700 dark:text-zinc-400">
            {{ __('Anda tidak memiliki izin mengirim pesan pada chat tiket ini.') }}
        </p>
    @endif
</div>
