<div class="space-y-6">
    @if (session()->has('toast_success'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:border-emerald-700/40 dark:bg-emerald-900/20 dark:text-emerald-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none text-emerald-700/70 hover:text-emerald-900 dark:text-emerald-300/70 dark:hover:text-emerald-200"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif
    @if (session()->has('toast_error'))
        <div x-data="{ open: true }" x-show="open" x-transition
            class="rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700 dark:border-red-800/50 dark:bg-red-950/30 dark:text-red-300">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_error') }}</span>
                <button type="button" @click="open = false"
                    class="text-base leading-none text-red-700/70 hover:text-red-900 dark:text-red-300/70 dark:hover:text-red-200"
                    aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
        <div class="space-y-2">
            <flux:heading size="xl">Penanganan insiden</flux:heading>
            <p class="font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $ticket->ticket_number ?? '—' }} — {{ $ticket->title }}</p>
            <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-medium {{ $phase['badge_class'] }}">
                {{ $phase['label'] }}
            </span>
        </div>
        <flux:button href="{{ route('tickets.index', auth()->user()?->seesOnlyResponderTicketListInNavigation() ? ['scope' => 'responder'] : []) }}" variant="ghost" wire:navigate>
            Kembali ke daftar
        </flux:button>
    </div>

    @if (! $canRecord && auth()->user()?->can('ticket.respond'))
        <div class="rounded-lg border border-amber-200 bg-amber-50/90 px-4 py-3 text-sm text-amber-950 dark:border-amber-800/60 dark:bg-amber-950/30 dark:text-amber-100" role="status">
            <p class="font-medium">Mode baca saja</p>
            @if ($canStartResponse)
                <p class="mt-1 text-amber-900/90 dark:text-amber-200/90">Tiket masih berada di fase Analysis. Gunakan tombol <strong class="font-semibold">Tangani Tiket</strong> di bawah untuk memindahkan ke fase Response; setelah itu form pencatatan tindakan akan aktif.</p>
            @elseif ($ticket->sub_status === \App\Models\Ticket::SUB_STATUS_RESOLUTION)
                <p class="mt-1 text-amber-900/90 dark:text-amber-200/90">Fase penanganan ditandai selesai (Resolution). Form pencatatan tindakan dinonaktifkan. Jika perlu menambah catatan, minta <strong class="font-semibold">koordinator</strong> membuka kembali fase respons dari rincian tiket di daftar tiket.</p>
            @else
                <p class="mt-1 text-amber-900/90 dark:text-amber-200/90">Anda tidak memiliki penugasan aktif pada tiket ini atau sub-status belum mengizinkan pencatatan tindakan. Hubungi koordinator untuk penugasan.</p>
            @endif
        </div>
    @endif

    <flux:card class="space-y-4 p-4 sm:p-5">
        <flux:heading size="lg">Ringkasan analisis terbaru</flux:heading>
        @if ($latestAnalysis === null)
            <flux:text class="text-zinc-500 dark:text-zinc-400">Tidak ada data analisis.</flux:text>
        @else
            <dl class="grid gap-3 text-sm sm:grid-cols-2">
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Keparahan (analisis)</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $latestAnalysis->severity ?? '—' }}</dd>
                </div>
                <div>
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Sub-status tiket</dt>
                    <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->sub_status ?? '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dampak</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ filled($latestAnalysis->impact) ? $latestAnalysis->impact : '—' }}</dd>
                </div>
                <div class="sm:col-span-2">
                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Rekomendasi</dt>
                    <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ filled($latestAnalysis->recommendation) ? $latestAnalysis->recommendation : '—' }}</dd>
                </div>
            </dl>
            @if ($latestAnalysis->iocs->isNotEmpty())
                <div>
                    <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">IOC (entri terbaru)</p>
                    <ul class="mt-2 divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                        @foreach ($latestAnalysis->iocs as $ioc)
                            <li class="px-3 py-2 text-sm" wire:key="resp-ioc-{{ $ioc->id }}">
                                <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $ioc->iocType?->ioc_type ?? 'IOC' }}</span>
                                <span class="mx-1 text-zinc-400">·</span>
                                <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">{{ $ioc->value }}</code>
                            </li>
                        @endforeach
                    </ul>
                </div>
            @endif
        @endif
    </flux:card>

    <section class="space-y-4" aria-labelledby="timeline-heading">
        <flux:heading size="lg" id="timeline-heading">Linimasa penanganan</flux:heading>
        @if ($timeline->isEmpty())
            <flux:card class="p-4 sm:p-5">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Belum ada tindakan atau perubahan fase yang tercatat di linimasa ini.</flux:text>
            </flux:card>
        @else
            <div class="relative space-y-0 border-l-2 border-zinc-200 pl-6 dark:border-zinc-600">
                @foreach ($timeline as $index => $entry)
                    @php
                        $entryKey = 'tl-'.$index.'-'.$entry['type'];
                    @endphp
                    <div class="relative pb-8 last:pb-0" wire:key="{{ $entryKey }}">
                        <span class="absolute -left-[9px] top-1.5 size-4 rounded-full border-2 border-white bg-sky-500 dark:border-zinc-900 dark:bg-sky-400" aria-hidden="true"></span>
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
                                        class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left hover:bg-zinc-50 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden"
                                    >
                                        <div class="min-w-0">
                                            <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">{{ $typeLabel }}</p>
                                            <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                                {{ $act->performer?->name ?? 'User #'.$act->performed_by }}
                                                · {{ $act->created_at?->format('d M Y H:i') ?? '—' }}
                                            </p>
                                        </div>
                                        <svg class="size-5 shrink-0 text-zinc-500 transition group-open:rotate-180" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                            <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                                        </svg>
                                    </summary>
                                    <div class="space-y-3 border-t border-zinc-200 px-4 py-4 text-sm dark:border-zinc-700">
                                        <p class="whitespace-pre-wrap text-zinc-800 dark:text-zinc-200">{{ $act->description }}</p>
                                        @if (filled($act->meta))
                                            <pre class="max-h-40 overflow-auto rounded bg-zinc-100 p-3 text-xs text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">{{ json_encode($act->meta, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
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
                                <p class="text-sm font-semibold text-emerald-800 dark:text-emerald-300">Penanganan ditandai selesai</p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
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
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">Perubahan sub-status</p>
                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-300">
                                    {{ ($data['from'] ?? '—') }} → {{ ($data['to'] ?? '—') }}
                                </p>
                                <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">
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
            <flux:heading size="lg">Mulai penanganan respons</flux:heading>
            <flux:text class="text-sm text-zinc-600 dark:text-zinc-400">Setelah Anda memulai, sub-status tiket menjadi Response dan Anda dapat mencatat tindakan mitigasi, eradikasi, atau pemulihan.</flux:text>
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
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <flux:textarea label="Deskripsi" wire:model="description" rows="5" placeholder="Uraian tindakan yang dilakukan (wajib)." />
                @error('description')
                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                        <span wire:loading.remove wire:target="saveAction">Simpan tindakan</span>
                        <span wire:loading wire:target="saveAction">Menyimpan…</span>
                    </flux:button>
                </div>
            </flux:card>
        </form>
    @endif

    @if ($canMarkResolved)
        <flux:card class="border-emerald-200/80 p-4 sm:p-5 dark:border-emerald-900/40">
            <flux:heading size="lg">Selesaikan fase respons</flux:heading>
            <flux:text class="mt-1 text-sm text-zinc-600 dark:text-zinc-400">Promosikan tiket ke sub-status Resolution setelah tindakan mencukupi. Penutupan tiket tetap oleh koordinator.</flux:text>
            <div class="mt-4 flex justify-end">
                <flux:button type="button" variant="primary" wire:click="markResolved" wire:confirm="Tandai penanganan respons selesai dan ubah sub-status ke Resolution?"
                    wire:loading.attr="disabled">
                    <span wire:loading.remove wire:target="markResolved">Tandai selesai (Resolution)</span>
                    <span wire:loading wire:target="markResolved">Memproses…</span>
                </flux:button>
            </div>
        </flux:card>
    @endif
</div>
