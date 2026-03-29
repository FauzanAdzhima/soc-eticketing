<div class="space-y-4">
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
        <flux:heading size="xl">Daftar Tiket</flux:heading>
        @can('ticket.create.pic')
            <flux:button size="sm" variant="primary" wire:click="openCreateModal">Buat tiket</flux:button>
        @endcan
    </div>

    <flux:card class="p-4 sm:p-5">
        <div class="overflow-x-auto rounded-lg border border-zinc-200 dark:border-zinc-700">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 dark:bg-zinc-800/80">
                    <tr>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">No. Tiket</th>
                        <th class="px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200">Judul</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 sm:table-cell">Status</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 md:table-cell">Kategori</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 lg:table-cell">Assigned To</th>
                        <th class="hidden px-3 py-2 text-left font-semibold text-zinc-700 dark:text-zinc-200 lg:table-cell">Dibuat</th>
                        <th class="px-3 py-2 text-right font-semibold text-zinc-700 dark:text-zinc-200">Aksi</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 bg-white dark:divide-zinc-700 dark:bg-zinc-900">
                    @forelse ($tickets as $ticket)
                        @php
                            $assignedNames = $ticket->assignments
                                ->where('is_active', true)
                                ->map(fn ($a) => $a->user?->name)
                                ->filter()
                                ->unique()
                                ->join(', ');
                        @endphp
                        <tr wire:key="ticket-{{ $ticket->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/50">
                            <td class="whitespace-nowrap px-3 py-2 font-mono text-xs font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $ticket->ticket_number ?? '—' }}
                            </td>
                            <td class="max-w-xs truncate px-3 py-2 text-zinc-900 dark:text-zinc-100" title="{{ $ticket->title }}">
                                {{ $ticket->title }}
                            </td>
                            <td class="hidden whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300 sm:table-cell">{{ $ticket->status }}</td>
                            <td class="hidden px-3 py-2 text-zinc-600 dark:text-zinc-300 md:table-cell">{{ $ticket->category?->name ?? '—' }}</td>
                            <td class="hidden max-w-[12rem] truncate px-3 py-2 text-zinc-600 dark:text-zinc-300 lg:table-cell" title="{{ $assignedNames }}">
                                {{ $assignedNames !== '' ? $assignedNames : '—' }}
                            </td>
                            <td class="hidden whitespace-nowrap px-3 py-2 text-zinc-600 dark:text-zinc-300 lg:table-cell">
                                {{ $ticket->created_at?->format('d M Y H:i') }}
                            </td>
                            <td class="whitespace-nowrap px-3 py-2 text-right">
                                <flux:button type="button" size="sm" variant="ghost" wire:click="openTicketDetail('{{ $ticket->public_id }}')">Detail</flux:button>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-3 py-8 text-center text-zinc-500 dark:text-zinc-400">Tidak ada tiket.</td>
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
                @if (! $createTicketShowFullForm)
                    <header>
                        <flux:heading size="xl">Pilih kategori insiden</flux:heading>
                        <flux:subheading>Pilih jenis kejadian yang paling sesuai. Anda masih bisa mengubah kategori di langkah berikutnya.</flux:subheading>
                    </header>

                    @if ($categories->isEmpty())
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Belum ada kategori insiden yang tersedia. Hubungi administrator.</flux:text>
                    @else
                        <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                            @foreach ($categories as $cat)
                                <button
                                    type="button"
                                    wire:click="selectCreateTicketCategory({{ $cat->id }})"
                                    wire:key="create-pick-cat-{{ $cat->id }}"
                                    class="group flex min-h-[5.5rem] w-full flex-col rounded-xl border border-zinc-200 bg-white p-4 text-left shadow-xs transition hover:border-sky-400 hover:shadow-md focus:outline-none focus-visible:ring-2 focus-visible:ring-sky-500 dark:border-zinc-700 dark:bg-zinc-900 dark:hover:border-sky-500/60 dark:hover:bg-zinc-800/80"
                                >
                                    <span class="font-semibold text-zinc-900 dark:text-zinc-100">{{ $cat->name }}</span>
                                    @if (filled($cat->description))
                                        <span class="mt-1 line-clamp-2 text-sm text-zinc-500 dark:text-zinc-400">{{ $cat->description }}</span>
                                    @else
                                        <span class="mt-auto pt-2 text-xs font-medium text-sky-600 opacity-0 transition group-hover:opacity-100 dark:text-sky-400">Pilih kategori ini →</span>
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
                    <flux:subheading>Laporan insiden baru (akun PIC). Data pelapor dapat disesuaikan sebelum dikirim.</flux:subheading>
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

                    <flux:input label="Subjek / judul" wire:model="formTitle" placeholder="contoh: Indikasi serangan pada sistem X" required />

                    <div class="grid grid-cols-1 gap-x-8 gap-y-6 md:grid-cols-2">
                        <flux:input label="Nama pelapor" wire:model="formReporterName" icon="user" required />
                        <flux:input label="No. WhatsApp / telepon" wire:model="formReporterPhone" icon="phone" />

                        <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700 md:col-span-2 dark:hover:bg-zinc-800/50">
                            <flux:checkbox wire:model.live="isOfficialEmployee" label="Pelapor pegawai / ASN (pakai organisasi terdaftar)" />
                        </div>

                        <flux:input type="email" label="Email pelapor" wire:model="formReporterEmail" icon="envelope" required />

                        <div wire:key="ticket-create-org-field">
                            @if ($isOfficialEmployee)
                                <flux:select label="Instansi / organisasi" wire:model="formReporterOrganizationId" icon="building-office" searchable>
                                    @foreach ($organizations as $org)
                                        <flux:select.option value="{{ $org->id }}">{{ $org->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:input label="Instansi / organisasi (teks)" wire:model="formReporterOrganizationName" icon="building-office-2"
                                    placeholder="contoh: Universitas X" />
                            @endif
                        </div>
                        @error('formReporterOrganizationId')
                            <span class="text-sm font-medium text-red-600 dark:text-red-400 md:col-span-2">{{ $message }}</span>
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
                            <flux:textarea label="Deskripsi kejadian" wire:model="formIncidentDescription" rows="5"
                                placeholder="Kronologi singkat kejadian…" />
                        </div>

                        <div class="space-y-3 border-t border-zinc-100 pt-4 dark:border-zinc-800 md:col-span-2">
                            <flux:label>Bukti dukung (opsional)</flux:label>
                            <input type="file" wire:model="evidenceFiles" multiple
                                class="block w-full cursor-pointer rounded-lg border border-zinc-200 bg-zinc-50 text-sm text-zinc-600 file:mr-4 file:rounded-l-lg file:border-0 file:bg-zinc-200 file:px-4 file:py-2.5 file:text-sm file:font-semibold file:text-zinc-800 hover:file:bg-zinc-300 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-300 dark:file:bg-zinc-700 dark:file:text-zinc-100" />
                            <div wire:loading wire:target="evidenceFiles" class="text-xs text-sky-600 animate-pulse dark:text-sky-400">
                                Mengunggah berkas…
                            </div>
                            @error('evidenceFiles.*')
                                <span class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</span>
                            @enderror

                            @if (! empty($evidenceFiles))
                                <div class="mt-2 grid grid-cols-2 gap-3 md:grid-cols-4">
                                    @foreach ($evidenceFiles as $index => $evidence)
                                        <div class="space-y-2 rounded border p-2 text-xs" wire:key="pic-evidence-{{ $index }}">
                                            @if ($this->isImageFile($evidence))
                                                <img src="{{ $evidence->temporaryUrl() }}" alt="Pratinjau" class="h-24 w-full rounded object-cover">
                                            @else
                                                <div class="flex h-24 w-full items-center justify-center rounded bg-zinc-100 text-zinc-500 dark:bg-zinc-800">
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

    <flux:modal name="ticket-detail-modal" class="md:w-[800px]" wire:close="closeTicketDetail">
        @if ($detailTicket)
            @php
                $assignedNames = $detailTicket->assignments
                    ->where('is_active', true)
                    ->map(fn ($a) => $a->user?->name)
                    ->filter()
                    ->unique()
                    ->join(', ');
            @endphp
            <div class="space-y-6">
                <header>
                    <flux:heading size="xl">{{ $detailTicket->title }}</flux:heading>
                    <p class="mt-1 font-mono text-sm text-zinc-500 dark:text-zinc-400">No. Tiket: {{ $detailTicket->ticket_number ?? '—' }}</p>
                </header>

                <flux:card class="space-y-4 p-4 sm:p-5">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->status }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Status laporan</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->report_status ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Sub-status</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->sub_status ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Laporan valid</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->report_is_valid ? 'Ya' : 'Belum' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Kategori</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->category?->name ?? '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Assigned To</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $assignedNames !== '' ? $assignedNames : '—' }}</dd>
                        </div>
                        <div>
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Dibuat</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->created_at?->format('d M Y H:i') }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Pembuat (internal)</dt>
                            <dd class="mt-0.5 text-zinc-900 dark:text-zinc-100">{{ $detailTicket->creator?->name ?? '—' }}</dd>
                        </div>
                        <div class="sm:col-span-2">
                            <dt class="font-medium text-zinc-500 dark:text-zinc-400">Deskripsi insiden</dt>
                            <dd class="mt-0.5 whitespace-pre-wrap text-zinc-900 dark:text-zinc-100">{{ $detailTicket->incident_description ?? '—' }}</dd>
                        </div>
                    </dl>
                    @if (filled($detailTicket->report_rejection_reason))
                        <div class="mt-4 rounded-lg border border-red-200 bg-red-50/80 p-3 dark:border-red-900/50 dark:bg-red-950/20">
                            <p class="text-sm font-medium text-red-800 dark:text-red-300">Alasan penolakan laporan</p>
                            <p class="mt-1 whitespace-pre-wrap text-sm text-red-900 dark:text-red-100">{{ $detailTicket->report_rejection_reason }}</p>
                        </div>
                    @endif
                </flux:card>

                <flux:card class="space-y-4 p-4 sm:p-5">
                    <flux:heading size="lg">Bukti dukung</flux:heading>
                    @if ($detailTicket->evidences->isEmpty())
                        <flux:text class="text-zinc-500 dark:text-zinc-400">Tidak ada lampiran pada tiket ini.</flux:text>
                    @else
                        <ul class="grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4">
                            @foreach ($detailTicket->evidences as $evidence)
                                @php
                                    $evidenceUrl = route('tickets.evidence.show', $evidence);
                                    $sizeKb = $evidence->size ? number_format((int) $evidence->size / 1024, 1) : null;
                                @endphp
                                <li class="overflow-hidden rounded-lg border border-zinc-200 bg-zinc-50 dark:border-zinc-700 dark:bg-zinc-800/80" wire:key="evidence-{{ $evidence->id }}">
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
                </flux:card>

                @can('verifyReport', $detailTicket)
                    <flux:card class="space-y-4 border-dashed border-amber-200 p-4 sm:p-5 dark:border-amber-800/50">
                        <div class="space-y-3">
                            <flux:heading size="lg">Keputusan laporan</flux:heading>
                            <flux:subheading>
                                Verifikasi jika laporan valid dan dapat dilanjutkan ke analis. Tolak jika laporan tidak valid, tidak relevan, atau indikasi false report—tindakan penolakan bersifat final dan memerlukan alasan.
                            </flux:subheading>
                        </div>

                        <div class="flex flex-wrap items-center gap-3">
                            <flux:button type="button" variant="primary" wire:click="verifyTicketReport" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="verifyTicketReport">Verifikasi laporan</span>
                                <span wire:loading wire:target="verifyTicketReport">Memproses…</span>
                            </flux:button>
                            <flux:button
                                type="button"
                                variant="danger"
                                wire:click="openRejectReportPanel"
                                wire:loading.attr="disabled"
                            >
                                Tolak laporan
                            </flux:button>
                        </div>

                        @if ($showRejectReportPanel)
                            <div class="space-y-4 rounded-lg border border-amber-200 bg-amber-50/80 p-4 dark:border-amber-800/50 dark:bg-amber-950/25">
                                <p class="text-sm text-amber-900 dark:text-amber-100">
                                    Penolakan bersifat final: tiket tidak akan ditugaskan ke analis. Wajib mengisi alasan yang jelas (minimal 15 karakter) sebelum mengonfirmasi.
                                </p>
                                <flux:textarea
                                    wire:model="rejectReportReason"
                                    rows="4"
                                    label="Alasan penolakan"
                                    placeholder="Jelaskan singkat mengapa laporan ditolak (wajib, minimal 15 karakter)."
                                />
                                @error('rejectReportReason')
                                    <p class="text-sm font-medium text-red-600 dark:text-red-400">{{ $message }}</p>
                                @enderror
                                <div class="flex flex-wrap items-center gap-3">
                                    <flux:button type="button" variant="ghost" wire:click="cancelRejectReport">
                                        Batal
                                    </flux:button>
                                    <flux:button
                                        type="button"
                                        variant="danger"
                                        wire:click="rejectTicketReport"
                                        wire:confirm="Laporan akan ditolak dan tiket ditutup untuk alur ini. Lanjutkan?"
                                        wire:loading.attr="disabled"
                                    >
                                        <span wire:loading.remove wire:target="rejectTicketReport">Konfirmasi tolak laporan</span>
                                        <span wire:loading wire:target="rejectTicketReport">Memproses…</span>
                                    </flux:button>
                                </div>
                            </div>
                        @endif
                    </flux:card>
                @endcan

                @can('assign', $detailTicket)
                    <flux:card class="space-y-4 border-dashed p-4 sm:p-5">
                        <flux:heading size="lg">Tugaskan ke analis</flux:heading>
                        <flux:subheading>Pilih analis utama untuk penanganan tiket ini.</flux:subheading>
                        <form wire:submit.prevent="assignAnalyst" class="space-y-4" wire:key="assign-analyst-{{ $detailTicket->public_id }}-{{ $detailTicket->report_status }}">
                            <div class="space-y-2" wire:key="assign-analyst-field-{{ $detailTicket->public_id }}">
                                <flux:select
                                    label="Analis"
                                    description="Pilih dari daftar (nama dan email). Analis yang dipilih menjadi penanggung jawab utama penanganan tiket."
                                    wire:model.live="assignAnalystUserId"
                                    icon="user"
                                >
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
                                    <span wire:loading.remove wire:target="assignAnalyst">Tugaskan</span>
                                    <span wire:loading wire:target="assignAnalyst">Menyimpan…</span>
                                </flux:button>
                            </div>
                        </form>
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
        backdrop-filter: blur(5px) !important;
        -webkit-backdrop-filter: blur(5px) !important;
    }

    dialog[data-modal="ticket-create-pic-modal"] {
        max-width: 900px !important;
        width: 90vw !important;
    }

    dialog[data-modal="ticket-detail-modal"] {
        max-width: 900px !important;
        width: 90vw !important;
    }
</style>
