<div class="space-y-6">
    @if (session()->has('toast_success'))
        <div x-data="{ open: true }" x-init="window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => open = false, 5000)" x-show="open"
            class="rounded-lg border border-success/40 bg-success/10 px-4 py-3 text-sm text-foreground">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_success') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none" aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif
    @if (session()->has('toast_error'))
        <div x-data="{ open: true }" x-init="window.scrollTo({ top: 0, behavior: 'smooth' }); setTimeout(() => open = false, 5000)" x-show="open"
            class="rounded-lg border border-danger/40 bg-danger/10 px-4 py-3 text-sm text-foreground">
            <div class="flex items-start justify-between gap-3">
                <span>{{ session('toast_error') }}</span>
                <button type="button" @click="open = false" class="text-base leading-none" aria-label="Tutup">&times;</button>
            </div>
        </div>
    @endif

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Analisis Insiden</flux:heading>
            <p class="mt-1 font-mono text-sm text-muted-foreground">{{ $ticket->ticket_number ?? '—' }} — {{ $ticket->title }}</p>
        </div>
        <flux:button href="{{ route('tickets.index', ['scope' => 'analyst', 'ticket' => $ticket->public_id]) }}" variant="ghost" wire:navigate>
            Kembali
        </flux:button>
    </div>

    @php
        $assignedNames = $ticket->assignments
            ->where('is_active', true)
            ->map(fn ($a) => $a->user?->name)
            ->filter()
            ->unique()
            ->join(', ');
        $reporterOrg = $ticket->organization?->name ?? $ticket->reporter_organization_name;
    @endphp

    <flux:card class="overflow-hidden p-0">
        <details class="group border-0">
            <summary
                class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-muted/80 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-primary [&::-webkit-details-marker]:hidden"
            >
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-foreground">Detail Tiket</span>
                    <span class="mt-0.5 block text-xs font-normal text-muted-foreground">Data laporan, pelapor, deskripsi insiden, dan bukti dukung</span>
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
            <div class="space-y-6 border-t border-border p-4 sm:p-5">
                <flux:heading size="lg">Ringkasan Laporan</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-muted-foreground">Status</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Status Laporan</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->report_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Sub-Status</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->sub_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Laporan Valid</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->report_is_valid ? 'Ya' : 'Belum' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Kategori</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Tingkat Keparahan (Laporan)</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->incident_severity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Waktu Kejadian</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->incident_time?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Petugas Tiket</dt>
                        <dd class="mt-0.5 text-foreground">{{ $assignedNames !== '' ? $assignedNames : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Dibuat</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->created_at?->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-muted-foreground">Pembuat Tiket (internal)</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->creator?->name ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-muted-foreground">Pelapor</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->reporter_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Email Pelapor</dt>
                        <dd class="mt-0.5 break-all text-foreground">{{ $ticket->reporter_email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-muted-foreground">Kontak Pelapor</dt>
                        <dd class="mt-0.5 text-foreground">{{ $ticket->reporter_phone ?? '—' }}</dd>
                    </div>
                    @if (filled($reporterOrg))
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-muted-foreground">Instansi / Organisasi Pelapor</dt>
                            <dd class="mt-0.5 text-foreground">{{ $reporterOrg }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-muted-foreground">Deskripsi Insiden</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap text-foreground">{{ $ticket->incident_description ?? '—' }}</dd>
                    </div>
                </dl>
                @if (filled($ticket->report_rejection_reason))
                    <div class="rounded-lg border border-danger/40 bg-danger/10 p-3">
                        <p class="text-sm font-medium text-danger">Alasan Penolakan Laporan</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-foreground">{{ $ticket->report_rejection_reason }}</p>
                    </div>
                @endif

                <div>
                    <flux:heading size="lg" class="mb-3">Bukti Dukung</flux:heading>
                    @if ($ticket->evidences->isEmpty())
                        <flux:text class="text-muted-foreground">Tidak ada lampiran pada tiket ini.</flux:text>
                    @else
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($ticket->evidences as $evidence)
                                @php
                                    $evidenceUrl = route('tickets.evidence.show', $evidence);
                                    $sizeKb = $evidence->size ? number_format((int) $evidence->size / 1024, 1) : null;
                                @endphp
                                <li class="overflow-hidden rounded-lg border border-border bg-muted" wire:key="analysis-evidence-{{ $evidence->id }}">
                                    <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-primary">
                                        <div class="relative h-28 w-full overflow-hidden bg-muted sm:h-32">
                                            @if ($evidence->isLikelyImage())
                                                <img src="{{ $evidenceUrl }}"
                                                    alt="{{ $evidence->original_name ?? 'Bukti gambar' }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                    decoding="async"
                                                    fetchpriority="low">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-xs font-medium text-foreground-secondary">
                                                    {{ strtoupper(\Illuminate\Support\Str::afterLast($evidence->original_name ?? 'file', '.')) ?: 'FILE' }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="border-t border-border p-2">
                                            <p class="truncate text-xs font-medium text-foreground" title="{{ $evidence->original_name }}">{{ $evidence->original_name ?? 'Lampiran' }}</p>
                                            @if ($sizeKb !== null)
                                                <p class="text-[11px] text-muted-foreground">{{ $sizeKb }} KB</p>
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
                        @livewire('ticket.chat', ['ticket' => $ticket], key('ticket-chat-analysis-' . $ticket->id))
                    </div>
                </details>
            </flux:card>
        </section>
    @endcan

    <section id="riwayat-analisis" class="scroll-mt-6 space-y-4" aria-labelledby="riwayat-analisis-heading">
        <flux:heading size="lg" id="riwayat-analisis-heading">Riwayat Analisis</flux:heading>
        @if ($ticket->analyses->isEmpty())
            <flux:card class="p-4 sm:p-5">
                <flux:text class="text-muted-foreground">Belum ada entri analisis untuk tiket ini.</flux:text>
            </flux:card>
        @else
            <div class="space-y-4">
                @foreach ($ticket->analyses as $analysis)
                    <flux:card class="space-y-4 p-4 sm:p-5" wire:key="analysis-history-{{ $analysis->id }}">
                        <div class="flex flex-col gap-2 border-b border-border pb-3 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-foreground">
                                    {{ $analysis->performer?->name ?? 'Analis #' . $analysis->performed_by }}
                                </p>
                                <p class="mt-0.5 text-xs text-muted-foreground">
                                    {{ $analysis->created_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-muted px-2.5 py-0.5 text-xs font-medium text-foreground">
                                {{ $analysis->severity ?? '—' }}
                            </span>
                        </div>

                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-muted-foreground">Dampak</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ filled($analysis->impact) ? $analysis->impact : '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-muted-foreground">Akar masalah</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ filled($analysis->root_cause) ? $analysis->root_cause : '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-muted-foreground">Rekomendasi</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ filled($analysis->recommendation) ? $analysis->recommendation : '—' }}</dd>
                            </div>
                            @if (filled($analysis->analysis_result))
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-muted-foreground">Ringkasan</dt>
                                    <dd class="mt-1 whitespace-pre-wrap text-foreground">{{ $analysis->analysis_result }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div>
                            <p class="text-sm font-medium text-foreground-secondary">IOC</p>
                            @if ($analysis->iocs->isEmpty())
                                <p class="mt-1 text-sm text-muted-foreground">Tidak ada IOC pada entri ini.</p>
                            @else
                                <ul class="mt-2 divide-y divide-border rounded-lg border border-border">
                                    @foreach ($analysis->iocs as $ioc)
                                        <li class="px-3 py-2 text-sm" wire:key="analysis-{{ $analysis->id }}-ioc-{{ $ioc->id }}">
                                            <span class="font-medium text-foreground">{{ $ioc->iocType?->ioc_type ?? 'IOC' }}</span>
                                            <span class="mx-1 text-muted-foreground">·</span>
                                            <code class="rounded bg-muted px-1 py-0.5 text-xs text-foreground">{{ $ioc->value }}</code>
                                            @if (filled($ioc->description))
                                                <p class="mt-1 text-xs text-muted-foreground">{{ $ioc->description }}</p>
                                            @endif
                                        </li>
                                    @endforeach
                                </ul>
                            @endif
                        </div>
                    </flux:card>
                @endforeach
            </div>
        @endif
    </section>

    <form wire:submit="submit" class="space-y-8">
        @if ($hasExistingOwnAnalysis)
            <flux:card class="space-y-3 border-info/40 bg-info/10 p-4 sm:p-5 dark:border-info/30">
                <flux:heading size="sm">Kebijakan penyimpanan</flux:heading>
                <flux:text class="text-sm text-foreground-secondary">
                    Secara bawaan, <strong>Simpan analisis</strong> memperbarui entri analisis terbaru milik Anda pada tiket ini (IOC lama diganti). Centang opsi di bawah jika Anda sengaja ingin menambah entri baru di riwayat (addendum).
                </flux:text>
                <flux:checkbox wire:model.live="saveAsAddendum" label="Simpan sebagai addendum (entri analisis baru)" />
            </flux:card>
        @endif

        <flux:card class="space-y-6 p-4 sm:p-5">
            <flux:heading size="lg">Ringkasan Analisis</flux:heading>

            <flux:select label="Tingkat Keparahan (Analisis)" wire:model="severity">
                <flux:select.option value="Low">Low</flux:select.option>
                <flux:select.option value="Medium">Medium</flux:select.option>
                <flux:select.option value="High">High</flux:select.option>
                <flux:select.option value="Critical">Critical</flux:select.option>
            </flux:select>
            @error('severity')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror

            <flux:textarea label="Dampak" wire:model="impact" rows="3" placeholder="Jelaskan dampak terhadap layanan atau data." />
            @error('impact')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror

            <flux:textarea label="Akar Masalah" wire:model="root_cause" rows="4" placeholder="Penyebab atau hipotesis utama." />
            @error('root_cause')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror

            <flux:textarea label="Rekomendasi" wire:model="recommendation" rows="4" placeholder="Langkah mitigasi atau tindak lanjut yang disarankan." />
            @error('recommendation')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror

            <flux:textarea label="Ringkasan Analisis" wire:model="analysisSummary" rows="3"
                placeholder="Ringkasan singkat untuk jejak audit atau pelaporan." />
            @error('analysisSummary')
                <p class="text-sm font-medium text-danger">{{ $message }}</p>
            @enderror
        </flux:card>

        <flux:card class="space-y-6 p-4 sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Indikator Kompromi (IOC)</flux:heading>
                    <flux:subheading>Tambahkan satu atau lebih IOC. Baris dengan nilai kosong diabaikan saat menyimpan.</flux:subheading>
                </div>
                <flux:button type="button" size="sm" variant="outline" wire:click="addIocRow">Tambah IOC</flux:button>
            </div>

            @if ($iocTypes->isEmpty())
                <flux:text class="text-warning">Belum ada tipe IOC di sistem. Jalankan seeder atau hubungi administrator.</flux:text>
            @else
                <div class="space-y-6">
                    @foreach ($iocRows as $index => $row)
                        <div wire:key="ioc-row-{{ $index }}" class="rounded-lg border border-border p-4">
                            <div class="mb-4 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-foreground-secondary">IOC #{{ $index + 1 }}</span>
                                @if (count($iocRows) > 1)
                                    <flux:button type="button" size="xs" variant="ghost" wire:click="removeIocRow({{ $index }})">
                                        Hapus baris
                                    </flux:button>
                                @endif
                            </div>
                            <div class="grid gap-4 sm:grid-cols-2">
                                <div class="sm:col-span-1">
                                    <flux:select label="Tipe" wire:model.live="iocRows.{{ $index }}.type_id">
                                        <option value="">—</option>
                                        @foreach ($iocTypes as $t)
                                            <flux:select.option value="{{ $t->id }}">{{ $t->ioc_type }}@if (filled($t->description)) — {{ $t->description }}@endif</flux:select.option>
                                        @endforeach
                                    </flux:select>
                                    @error('iocRows.'.$index.'.type_id')
                                        <p class="mt-1 text-sm font-medium text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <flux:input label="Nilai" wire:model="iocRows.{{ $index }}.value" placeholder="contoh: 192.0.2.1 atau domain.com" />
                                    @error('iocRows.'.$index.'.value')
                                        <p class="mt-1 text-sm font-medium text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <flux:textarea label="Deskripsi (opsional)" wire:model="iocRows.{{ $index }}.description" rows="2" />
                                    @error('iocRows.'.$index.'.description')
                                        <p class="mt-1 text-sm font-medium text-danger">{{ $message }}</p>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </flux:card>

        <div class="flex flex-wrap items-center justify-end gap-3">
            <flux:button href="{{ route('tickets.index', ['scope' => 'analyst', 'ticket' => $ticket->public_id]) }}" variant="ghost" wire:navigate>Batal</flux:button>
            <flux:button type="submit" variant="primary" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="submit">Simpan Analisis</span>
                <span wire:loading wire:target="submit">Menyimpan…</span>
            </flux:button>
        </div>
    </form>
</div>
