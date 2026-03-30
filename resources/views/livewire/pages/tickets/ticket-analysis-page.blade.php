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

    <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">Analisis insiden</flux:heading>
            <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">{{ $ticket->ticket_number ?? '—' }} — {{ $ticket->title }}</p>
        </div>
        <flux:button href="{{ route('tickets.index', ['scope' => 'analyst', 'ticket' => $ticket->public_id]) }}" variant="ghost" wire:navigate>
            Kembali ke daftar tiket dianalisis
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
                class="flex cursor-pointer list-none items-center justify-between gap-3 px-4 py-3.5 text-left outline-none transition hover:bg-zinc-50 focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-sky-500 dark:hover:bg-zinc-800/60 [&::-webkit-details-marker]:hidden"
            >
                <div class="min-w-0 flex-1">
                    <span class="block text-sm font-semibold text-zinc-900 dark:text-zinc-100">Detail tiket</span>
                    <span class="mt-0.5 block text-xs font-normal text-zinc-500 dark:text-zinc-400">Data laporan, pelapor, deskripsi insiden, dan bukti dukung</span>
                </div>
                <svg
                    class="size-5 shrink-0 text-zinc-500 transition-transform duration-200 group-open:rotate-180 dark:text-zinc-400"
                    xmlns="http://www.w3.org/2000/svg"
                    viewBox="0 0 20 20"
                    fill="currentColor"
                    aria-hidden="true"
                >
                    <path fill-rule="evenodd" d="M5.23 7.21a.75.75 0 011.06.02L10 11.168l3.71-3.938a.75.75 0 111.08 1.04l-4.25 4.5a.75.75 0 01-1.08 0l-4.25-4.5a.75.75 0 01.02-1.06z" clip-rule="evenodd" />
                </svg>
            </summary>
            <div class="space-y-6 border-t border-zinc-200 p-4 sm:p-5 dark:border-zinc-700">
                <flux:heading size="lg">Ringkasan laporan</flux:heading>
                <dl class="grid gap-3 text-sm sm:grid-cols-2">
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->status }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status laporan</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->report_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Sub-status</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->sub_status ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Laporan valid</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->report_is_valid ? 'Ya' : 'Belum' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kategori</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->category?->name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Tingkat keparahan (laporan)</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->incident_severity ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Waktu kejadian</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->incident_time?->format('d M Y H:i') ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Assigned To</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $assignedNames !== '' ? $assignedNames : '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dibuat</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->created_at?->format('d M Y H:i') }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pembuat tiket (internal)</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->creator?->name ?? '—' }}</dd>
                    </div>
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pelapor</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_name ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Email pelapor</dt>
                        <dd class="mt-0.5 break-all text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_email ?? '—' }}</dd>
                    </div>
                    <div>
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kontak pelapor</dt>
                        <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $ticket->reporter_phone ?? '—' }}</dd>
                    </div>
                    @if (filled($reporterOrg))
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Instansi / organisasi pelapor</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $reporterOrg }}</dd>
                        </div>
                    @endif
                    <div class="sm:col-span-2">
                        <dt class="font-medium text-zinc-500 dark:text-zinc-400">Deskripsi insiden</dt>
                        <dd class="mt-0.5 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $ticket->incident_description ?? '—' }}</dd>
                    </div>
                </dl>
                @if (filled($ticket->report_rejection_reason))
                    <div class="rounded-lg border border-red-200 bg-red-50/80 p-3 dark:border-red-900/50 dark:bg-red-950/20">
                        <p class="text-sm font-medium text-red-800 dark:text-red-300">Alasan penolakan laporan</p>
                        <p class="mt-1 whitespace-pre-wrap text-sm text-red-900 dark:text-red-100">{{ $ticket->report_rejection_reason }}</p>
                    </div>
                @endif

                <div>
                    <flux:heading size="lg" class="mb-3">Bukti dukung</flux:heading>
                    @if ($ticket->evidences->isEmpty())
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Tidak ada lampiran pada tiket ini.</flux:text>
                    @else
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($ticket->evidences as $evidence)
                                @php
                                    $evidenceUrl = route('tickets.evidence.show', $evidence);
                                    $sizeKb = $evidence->size ? number_format((int) $evidence->size / 1024, 1) : null;
                                @endphp
                                <li class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80" wire:key="analysis-evidence-{{ $evidence->id }}">
                                    <a href="{{ $evidenceUrl }}" target="_blank" rel="noopener noreferrer" class="block focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500">
                                        <div class="relative h-28 w-full overflow-hidden bg-zinc-200 sm:h-32 dark:bg-zinc-700">
                                            @if ($evidence->isLikelyImage())
                                                <img src="{{ $evidenceUrl }}"
                                                    alt="{{ $evidence->original_name ?? 'Bukti gambar' }}"
                                                    class="h-full w-full object-cover"
                                                    loading="lazy"
                                                    decoding="async"
                                                    fetchpriority="low">
                                            @else
                                                <div class="flex h-full w-full items-center justify-center text-xs font-medium text-zinc-600 dark:text-zinc-300">
                                                    {{ strtoupper(\Illuminate\Support\Str::afterLast($evidence->original_name ?? 'file', '.')) ?: 'FILE' }}
                                                </div>
                                            @endif
                                        </div>
                                        <div class="border-t border-zinc-200 p-2 dark:border-zinc-600">
                                            <p class="truncate text-xs font-medium text-zinc-800 dark:text-zinc-100" title="{{ $evidence->original_name }}">{{ $evidence->original_name ?? 'Lampiran' }}</p>
                                            @if ($sizeKb !== null)
                                                <p class="text-[11px] text-zinc-500 dark:text-zinc-400">{{ $sizeKb }} KB</p>
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

    <section id="riwayat-analisis" class="scroll-mt-6 space-y-4" aria-labelledby="riwayat-analisis-heading">
        <flux:heading size="lg" id="riwayat-analisis-heading">Riwayat analisis</flux:heading>
        @if ($ticket->analyses->isEmpty())
            <flux:card class="p-4 sm:p-5">
                <flux:text class="text-zinc-500 dark:text-zinc-400">Belum ada entri analisis untuk tiket ini.</flux:text>
            </flux:card>
        @else
            <div class="space-y-4">
                @foreach ($ticket->analyses as $analysis)
                    <flux:card class="space-y-4 p-4 sm:p-5" wire:key="analysis-history-{{ $analysis->id }}">
                        <div class="flex flex-col gap-2 border-b border-zinc-200 pb-3 dark:border-zinc-700 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <p class="text-sm font-semibold text-zinc-900 dark:text-zinc-100">
                                    {{ $analysis->performer?->name ?? 'Analis #' . $analysis->performed_by }}
                                </p>
                                <p class="mt-0.5 text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $analysis->created_at?->format('d M Y H:i') ?? '—' }}
                                </p>
                            </div>
                            <span class="inline-flex w-fit rounded-full bg-zinc-100 px-2.5 py-0.5 text-xs font-medium text-zinc-800 dark:bg-zinc-800 dark:text-zinc-200">
                                {{ $analysis->severity ?? '—' }}
                            </span>
                        </div>

                        <dl class="grid gap-3 text-sm sm:grid-cols-2">
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dampak</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ filled($analysis->impact) ? $analysis->impact : '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Akar masalah</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ filled($analysis->root_cause) ? $analysis->root_cause : '—' }}</dd>
                            </div>
                            <div class="sm:col-span-2">
                                <dt class="font-medium text-zinc-500 dark:text-zinc-400">Rekomendasi</dt>
                                <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ filled($analysis->recommendation) ? $analysis->recommendation : '—' }}</dd>
                            </div>
                            @if (filled($analysis->analysis_result))
                                <div class="sm:col-span-2">
                                    <dt class="font-medium text-zinc-500 dark:text-zinc-400">Ringkasan</dt>
                                    <dd class="mt-1 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $analysis->analysis_result }}</dd>
                                </div>
                            @endif
                        </dl>

                        <div>
                            <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300">IOC</p>
                            @if ($analysis->iocs->isEmpty())
                                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">Tidak ada IOC pada entri ini.</p>
                            @else
                                <ul class="mt-2 divide-y divide-zinc-200 rounded-lg border border-zinc-200 dark:divide-zinc-700 dark:border-zinc-700">
                                    @foreach ($analysis->iocs as $ioc)
                                        <li class="px-3 py-2 text-sm" wire:key="analysis-{{ $analysis->id }}-ioc-{{ $ioc->id }}">
                                            <span class="font-medium text-zinc-800 dark:text-zinc-200">{{ $ioc->iocType?->ioc_type ?? 'IOC' }}</span>
                                            <span class="mx-1 text-zinc-400">·</span>
                                            <code class="rounded bg-zinc-100 px-1 py-0.5 text-xs text-zinc-900 dark:bg-zinc-800 dark:text-zinc-100">{{ $ioc->value }}</code>
                                            @if (filled($ioc->description))
                                                <p class="mt-1 text-xs text-zinc-600 dark:text-zinc-400">{{ $ioc->description }}</p>
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
            <flux:card class="space-y-3 border-sky-200 bg-sky-50/80 p-4 dark:border-sky-900/50 dark:bg-sky-950/20 sm:p-5">
                <flux:heading size="sm">Kebijakan penyimpanan</flux:heading>
                <flux:text class="text-sm text-zinc-700 dark:text-zinc-300">
                    Secara bawaan, <strong>Simpan analisis</strong> memperbarui entri analisis terbaru milik Anda pada tiket ini (IOC lama diganti). Centang opsi di bawah jika Anda sengaja ingin menambah entri baru di riwayat (addendum).
                </flux:text>
                <flux:checkbox wire:model.live="saveAsAddendum" label="Simpan sebagai addendum (entri analisis baru)" />
            </flux:card>
        @endif

        <flux:card class="space-y-6 p-4 sm:p-5">
            <flux:heading size="lg">Ringkasan analisis</flux:heading>

            <flux:select label="Tingkat keparahan (analisis)" wire:model="severity">
                <flux:select.option value="Low">Low</flux:select.option>
                <flux:select.option value="Medium">Medium</flux:select.option>
                <flux:select.option value="High">High</flux:select.option>
                <flux:select.option value="Critical">Critical</flux:select.option>
            </flux:select>
            @error('severity')
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:textarea label="Dampak" wire:model="impact" rows="3" placeholder="Jelaskan dampak terhadap layanan atau data." />
            @error('impact')
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:textarea label="Akar masalah" wire:model="root_cause" rows="4" placeholder="Penyebab atau hipotesis utama." />
            @error('root_cause')
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:textarea label="Rekomendasi" wire:model="recommendation" rows="4" placeholder="Langkah mitigasi atau tindak lanjut yang disarankan." />
            @error('recommendation')
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror

            <flux:textarea label="Ringkasan eksekutif (opsional)" wire:model="analysisSummary" rows="3"
                placeholder="Ringkasan singkat untuk jejak audit atau pelaporan." />
            @error('analysisSummary')
                <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
            @enderror
        </flux:card>

        <flux:card class="space-y-6 p-4 sm:p-5">
            <div class="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <flux:heading size="lg">Indikator kompromi (IOC)</flux:heading>
                    <flux:subheading>Tambahkan satu atau lebih IOC. Baris dengan nilai kosong diabaikan saat menyimpan.</flux:subheading>
                </div>
                <flux:button type="button" size="sm" variant="outline" wire:click="addIocRow">Tambah IOC</flux:button>
            </div>

            @if ($iocTypes->isEmpty())
                <flux:text class="text-amber-700 dark:text-amber-300">Belum ada tipe IOC di sistem. Jalankan seeder atau hubungi administrator.</flux:text>
            @else
                <div class="space-y-6">
                    @foreach ($iocRows as $index => $row)
                        <div wire:key="ioc-row-{{ $index }}" class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                            <div class="mb-4 flex items-center justify-between gap-2">
                                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300">IOC #{{ $index + 1 }}</span>
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
                                        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <flux:input label="Nilai" wire:model="iocRows.{{ $index }}.value" placeholder="contoh: 192.0.2.1 atau domain.com" />
                                    @error('iocRows.'.$index.'.value')
                                        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                    @enderror
                                </div>
                                <div class="sm:col-span-2">
                                    <flux:textarea label="Deskripsi (opsional)" wire:model="iocRows.{{ $index }}.description" rows="2" />
                                    @error('iocRows.'.$index.'.description')
                                        <p class="mt-1 text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
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
                <span wire:loading.remove wire:target="submit">Simpan analisis</span>
                <span wire:loading wire:target="submit">Menyimpan…</span>
            </flux:button>
        </div>
    </form>
</div>
