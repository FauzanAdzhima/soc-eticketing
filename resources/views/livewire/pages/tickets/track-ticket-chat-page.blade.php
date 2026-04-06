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
            <flux:heading size="lg">Lacak tiket — {{ $ticket->ticket_number }}</flux:heading>
        </div>

        <div class="grid grid-cols-1 items-start gap-6 lg:grid-cols-12">
            <aside
                class="lg:sticky lg:top-6 lg:col-span-4 lg:max-h-[calc(100dvh-7rem)] lg:overflow-hidden lg:self-start xl:col-span-3">
                <flux:card
                    class="flex max-h-full min-h-0 flex-col overflow-hidden p-0 lg:h-full">
                    <div
                        class="shrink-0 border-b border-zinc-200/80 px-4 py-3 dark:border-zinc-600/60 sm:px-5">
                        <flux:heading size="sm">Laporan awal</flux:heading>
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
                            <ul class="mt-1 list-inside list-disc text-sm text-zinc-800 dark:text-zinc-200">
                                @foreach ($ticket->evidences as $evidence)
                                    <li class="break-all">{{ $evidence->original_name }}</li>
                                @endforeach
                            </ul>
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
