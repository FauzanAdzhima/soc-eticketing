@php
    $severityLabels = [
        'Low' => 'Rendah',
        'Medium' => 'Sedang',
        'High' => 'Tinggi',
        'Critical' => 'Kritis',
    ];
    $severityDisplay =
        isset($severityLabels[$ticket->incident_severity]) ? $severityLabels[$ticket->incident_severity] : ($ticket->incident_severity ?? '—');
    $organizationDisplay =
        $ticket->organization?->name ?? (filled($ticket->reporter_organization_name) ? $ticket->reporter_organization_name : null);
@endphp

<div>
    <flux:main container>
        <x-dynamic-breadcrumb :items="[['href' => route('tickets.track.search'), 'label' => 'Cari Tiket']]"
            current="Lacak tiket" />

        <div class="mb-4">
            <flux:heading size="lg">Lacak Tiket — {{ $ticket->ticket_number }}</flux:heading>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <aside
                class="lg:sticky lg:top-6 lg:col-span-4 lg:max-h-[calc(100dvh-7rem)] lg:overflow-hidden lg:self-start xl:col-span-3">
                <flux:card
                    class="flex max-h-full min-h-0 flex-col overflow-hidden p-0 lg:h-full">
                    <div
                        class="shrink-0 border-b border-zinc-200/80 px-4 py-3 dark:border-zinc-600/60 sm:px-5">
                        <flux:heading size="sm">Laporan Awal</flux:heading>
                    </div>
                    <div class="min-h-0 flex-1 space-y-4 overflow-y-auto overscroll-y-contain p-4 sm:p-5"
                        role="region" aria-label="Detail laporan awal">

                    <dl class="space-y-3 text-sm">
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Subjek</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->title }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pelapor</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_name }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Email</dt>
                            <dd class="mt-0.5 break-all text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_email }}</dd>
                        </div>
                        @if (filled($ticket->reporter_phone))
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Telepon / WhatsApp</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_phone }}</dd>
                            </div>
                        @endif
                        @if (filled($organizationDisplay))
                            <div>
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Organisasi</dt>
                                <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $organizationDisplay }}</dd>
                            </div>
                        @endif
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kategori</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                {{ $ticket->category?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Tingkat keparahan</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $severityDisplay }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Waktu kejadian</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                {{ $ticket->incident_time?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Waktu dilaporkan</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">
                                {{ $ticket->reported_at?->format('d/m/Y H:i') ?? '—' }}</dd>
                        </div>
                    </dl>

                    <div>
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Deskripsi insiden</p>
                        <flux:text class="mt-1 whitespace-pre-wrap text-sm">{{ $ticket->incident_description }}</flux:text>
                    </div>

                    @if ($ticket->evidences->isNotEmpty())
                        <div>
                            <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400">Bukti lampiran</p>
                            <div class="mt-2 grid grid-cols-2 gap-2 sm:grid-cols-3" x-data="{
                                openPreview(url, name) {
                                    const w = window.open('about:blank', '_blank');
                                    if (!w) return;
                                    w.document.title = name;
                                    w.document.body.style.cssText = 'margin:0;background:#0b1020;min-height:100vh;display:flex;align-items:center;justify-content:center';
                                    const img = w.document.createElement('img');
                                    img.alt = name;
                                    img.style.cssText = 'max-width:100vw;max-height:100vh;object-fit:contain';
                                    w.document.body.appendChild(img);
                                    fetch(url, { credentials: 'include' })
                                        .then(r => r.ok ? r.blob() : Promise.reject())
                                        .then(b => img.src = URL.createObjectURL(b))
                                        .catch(() => {
                                            w.document.body.innerHTML = '';
                                            const a = w.document.createElement('a');
                                            a.href = url; a.target = '_self';
                                            a.textContent = 'Klik untuk membuka file.';
                                            a.style.cssText = 'color:#fff;font-family:sans-serif;font-size:14px';
                                            w.document.body.appendChild(a);
                                        });
                                }
                            }">
                                @foreach ($ticket->evidences as $evidence)
                                    @php
                                        $evidenceUrl = route('tickets.track.evidence', [
                                            'ticket' => $ticket,
                                            'token' => $token,
                                            'evidence' => $evidence,
                                        ]);
                                    @endphp
                                    @if ($evidence->isLikelyImage())
                                        <button type="button"
                                            class="group relative overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 transition hover:border-zinc-400 focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-500"
                                            @click="openPreview(@js($evidenceUrl), @js($evidence->original_name))"
                                            title="{{ $evidence->original_name }}">
                                            <img src="{{ $evidenceUrl }}" alt="{{ $evidence->original_name }}"
                                                class="aspect-square w-full object-cover transition group-hover:scale-105"
                                                loading="lazy" />
                                            <span
                                                class="absolute inset-x-0 bottom-0 truncate bg-gradient-to-t from-black/60 to-transparent px-1.5 pb-1 pt-4 text-[11px] text-white">
                                                {{ $evidence->original_name }}
                                            </span>
                                        </button>
                                    @else
                                        <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener"
                                            class="flex flex-col items-center justify-center gap-1 rounded-lg border border-zinc-200 bg-zinc-50 p-3 text-center transition hover:border-zinc-400 hover:bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 dark:hover:border-zinc-500 dark:hover:bg-zinc-700"
                                            title="{{ $evidence->original_name }}">
                                            <svg class="size-7 text-zinc-400 dark:text-zinc-500"
                                                xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"
                                                stroke-width="1.5" stroke="currentColor">
                                                <path stroke-linecap="round" stroke-linejoin="round"
                                                    d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" />
                                            </svg>
                                            <span
                                                class="w-full truncate text-[11px] text-zinc-600 dark:text-zinc-300">
                                                {{ $evidence->original_name }}
                                            </span>
                                        </a>
                                    @endif
                                @endforeach
                            </div>
                        </div>
                    @endif

                    @if ($ticket->isReportRejected() && filled($ticket->report_rejection_reason))
                        <div
                            class="rounded-lg border border-rose-200 bg-rose-50/90 p-3 text-sm text-rose-900 dark:border-rose-900/50 dark:bg-rose-950/40 dark:text-rose-100">
                            <p class="font-medium">Laporan ditolak</p>
                            <p class="mt-1 whitespace-pre-wrap">{{ $ticket->report_rejection_reason }}</p>
                        </div>
                    @endif
                    </div>
                </flux:card>
            </aside>

            <div class="min-w-0 lg:col-span-8 xl:col-span-9">
                @livewire('ticket.chat', ['ticket' => $ticket, 'guestToken' => $token], key('ticket-chat-guest-' . $ticket->id))
            </div>
        </div>
    </flux:main>
</div>
