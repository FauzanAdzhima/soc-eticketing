<section>
    <div wire:poll.60s="keepAlive" class="hidden"></div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($categories as $category)
            <flux:card wire:click="openTicketForm({{ $category->id }})"
                class="cursor-pointer transition-all hover:ring-2 hover:ring-primary/40">
                <div class="flex items-center gap-2">
                    <flux:heading>{{ $category->name }}</flux:heading>

                    <div class="group relative inline-flex" wire:click.stop>
                        <button type="button"
                            class="inline-flex h-5 w-5 items-center justify-center rounded-full border border-border text-muted-foreground transition hover:bg-muted hover:text-foreground focus:outline-none focus-visible:ring-2 focus-visible:ring-primary"
                            aria-label="Info {{ $category->name }}">
                            <svg class="h-3.5 w-3.5" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                <path fill-rule="evenodd"
                                    d="M18 10A8 8 0 1 1 2 10a8 8 0 0 1 16 0Zm-7.25-3a.75.75 0 1 1-1.5 0 .75.75 0 0 1 1.5 0Zm-.75 2.25a.75.75 0 0 0-.75.75v4a.75.75 0 0 0 1.5 0v-4a.75.75 0 0 0-.75-.75Z"
                                    clip-rule="evenodd" />
                            </svg>
                        </button>

                        <div
                            class="pointer-events-none invisible absolute bottom-full left-1/2 z-20 mb-2 w-72 -translate-x-1/2 rounded-lg border border-border bg-surface px-3 py-2 text-xs text-foreground opacity-0 shadow-lg transition group-hover:visible group-hover:opacity-100 group-focus-within:visible group-focus-within:opacity-100">
                            {{ filled($category->description) ? $category->description : 'Keterangan kategori belum tersedia.' }}
                        </div>
                    </div>
                </div>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="incident-modal" class="md:w-[800px]">
        <div class="space-y-1">
            @if ($isSuccess)
                <div class="text-center py-10 animate-fade-in">
                    <flux:icon.check-circle variant="solid" class="mx-auto size-16 text-success" />
                    <flux:heading size="xl" class="mt-4">Laporan Terkirim!</flux:heading>
                    <flux:subheading>Nomor tiket Anda</flux:subheading>
                    <div
                        class="mt-4 rounded-lg border border-border bg-muted p-4 font-mono text-lg font-bold select-all !text-foreground dark:border-zinc-600 dark:bg-zinc-700 dark:!text-zinc-50">
                        {{ $createdTicketNo }}
                    </div>
                    <flux:subheading class="mt-6">Token akses (lacak tiket)</flux:subheading>
                    <div
                        class="mt-2 rounded-lg border border-border bg-muted p-4 font-mono text-sm font-semibold break-all select-all !text-foreground dark:border-zinc-600 dark:bg-zinc-700 dark:!text-zinc-50">
                        {{ $createdReporterChatToken }}
                    </div>
                    <flux:text class="mx-auto mt-4 max-w-md text-center text-sm">
                        Simpan nomor tiket dan token ini. Setelah email aktif, Anda juga akan menerima tautan lacak lewat
                        surel. Untuk melacak sekarang, gunakan menu
                        <a href="{{ route('tickets.track.search') }}"
                            class="font-medium text-primary underline decoration-primary/40 underline-offset-2 hover:decoration-primary">Cari
                            Tiket</a>
                        di beranda.
                    </flux:text>
                    <div class="mt-8">
                        <flux:button wire:click="closeSuccess" variant="primary">Tutup & Selesai</flux:button>
                    </div>
                </div>
            @else
                <header class="z-20 border-border px-1 pb-4 pt-1">
                    <flux:heading size="xl">Form Laporan Insiden</flux:heading>
                    <flux:subheading class="mt-2 inline-flex items-center gap-2 rounded-lg border border-border bg-muted dark:bg-white/[8%] px-3 py-1 text-sm">
                        Kategori: {{ $this->selectedCategory?->name ?? '' }}
                    </flux:subheading>
                </header>                

                <flux:separator class="h-4 mb-2" />

                <div class="incident-modal-form-scroll max-h-[65vh] overflow-y-auto pr-1">
                    <form wire:submit="submitIncident" class="space-y-5 sm:space-y-6">
                    <flux:input label="Subjek Aduan" wire:model.defer="formData.title"
                        placeholder="cont: Indikasi serangan pada sistem X" required />

                    <div class="grid grid-cols-1 gap-x-6 gap-y-4 md:grid-cols-2 sm:gap-y-6">
                        <flux:input label="Nama Lengkap" wire:model.defer="formData.reporter_name" icon="user"
                            placeholder="John Doe" required />

                        <flux:input label="No. WhatsApp/Telepon" wire:model.defer="formData.reporter_phone"
                            icon="phone" placeholder="081234567890" required />

                        <div class="rounded-lg border border-border-strong p-4 md:col-span-2 hover:bg-muted/80">
                            <flux:checkbox wire:model.live="isOfficialEmployee"
                                label="Saya adalah pegawai / ASN Pemprov Kepri" />
                        </div>

                        <flux:input type="email" label="Email {{ $isOfficialEmployee ? 'Dinas' : '' }}"
                            wire:model.defer="formData.reporter_email" icon="envelope"
                            placeholder="{{ $isOfficialEmployee ? 'john.doe@kepriprov.go.id' : 'john.doe@org.com' }}"
                            required />

                        <div wire:key="org-field-container">
                            @if ($isOfficialEmployee)
                                <flux:select label="Instansi/Organisasi Pelapor"
                                    wire:model="formData.reporter_organization_id" icon="building-office" searchable>
                                    @foreach ($organizations as $org)
                                        <flux:select.option value="{{ $org->id }}">{{ $org->name }}</flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                <flux:input label="Instansi/Organisasi Pelapor"
                                    wire:model.defer="formData.reporter_organization_name" icon="building-office-2"
                                    placeholder="cont: Universitas X" required />
                            @endif
                        </div>

                        <flux:separator class="md:col-span-2"></flux:separator>

                        <flux:select label="Tingkat Keparahan" wire:model="formData.incident_severity">
                            <flux:select.option value="Low">Rendah (Low)</flux:select.option>
                            <flux:select.option value="Medium">Sedang (Medium)</flux:select.option>
                            <flux:select.option value="High">Tinggi (High)</flux:select.option>
                            <flux:select.option value="Critical">Kritis (Critical)</flux:select.option>
                        </flux:select>

                        <flux:input type="datetime-local" label="Waktu Kejadian" wire:model="formData.incident_time" />

                        <div class="md:col-span-2">
                            <flux:textarea label="Deskripsi Kejadian" wire:model="formData.incident_description"
                                placeholder="Jelaskan kronologi singkat kejadian..." rows="5" />
                        </div>

                        <div class="md:col-span-2 space-y-3 border-t border-border pt-3"
                            x-data="{ uploading: false, progress: 0 }"
                            x-on:livewire-upload-start.window="uploading = true; progress = 0"
                            x-on:livewire-upload-finish.window="uploading = false; progress = 100"
                            x-on:livewire-upload-error.window="uploading = false"
                            x-on:livewire-upload-progress.window="progress = $event.detail.progress">
                            <flux:label>Bukti Dukung (Screenshot/Foto)</flux:label>
                            <label
                                class="flex items-center justify-between gap-3 rounded-lg border border-border px-4 py-3 text-sm text-foreground-secondary transition hover:border-border-strong hover:bg-muted/80 hover:cursor-pointer">
                                <span class="truncate">Drop file atau klik untuk upload (JPG, JPEG, PNG, GIF, WEBP hingga 5MB)</span>
                                <input type="file" wire:model="evidenceFiles" multiple accept="image/jpeg,image/png,image/gif,image/webp" class="hidden" />
                                <span
                                    class="shrink-0 rounded-md bg-muted px-3 py-1 text-xs font-medium text-foreground">
                                    Pilih File
                                </span>
                            </label>
                            <div x-show="uploading" x-cloak class="space-y-1">
                                <div class="h-2 overflow-hidden rounded-full bg-muted">
                                    <div class="h-full bg-primary transition-all duration-150" :style="`width: ${progress}%`"></div>
                                </div>
                                <p class="text-xs text-primary">
                                    Mengunggah file... <span x-text="`${progress}%`"></span>
                                </p>
                            </div>
                            @error('evidenceFiles.*')
                                <span class="text-xs font-medium text-danger">{{ $message }}</span>
                            @enderror

                            @if (!empty($evidenceFiles))
                                <div class="mt-3 flex flex-col gap-2" x-data="{
                                    async openPreview(url, name) {
                                        const previewWindow = window.open('about:blank', '_blank');
                                        if (!previewWindow) return;
                                        const safeName = String(name || 'Preview Gambar');
                                        const safeUrl = String(url || '');
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
                                }">
                                    @foreach ($evidenceFiles as $index => $evidence)
                                        <div wire:key="evidence-preview-{{ $index }}" role="button" tabindex="0"
                                            class="relative flex w-full items-center gap-3 rounded-lg border border-border p-2 pr-10 text-left transition hover:bg-muted/80"
                                            @click="openPreview(@js($evidence->temporaryUrl()), @js($this->evidenceOriginalName($evidence)))"
                                            @keydown.enter.prevent="openPreview(@js($evidence->temporaryUrl()), @js($this->evidenceOriginalName($evidence)))"
                                            @keydown.space.prevent="openPreview(@js($evidence->temporaryUrl()), @js($this->evidenceOriginalName($evidence)))">
                                            <span class="block">
                                                <img src="{{ $evidence->temporaryUrl() }}" alt="Preview evidence"
                                                    class="h-12 w-12 rounded object-cover ring-1 ring-border">
                                            </span>
                                            <div class="min-w-0 flex-1">
                                                <p class="truncate text-sm font-medium text-foreground">
                                                    {{ $this->evidenceOriginalName($evidence) }}
                                                </p>
                                                <p class="text-xs text-muted-foreground">
                                                    {{ $this->evidenceSizeKb($evidence) }} KB
                                                </p>
                                            </div>
                                            <button type="button"
                                                class="absolute right-2 top-2 inline-flex h-6 w-6 items-center justify-center rounded-full text-muted-foreground transition hover:bg-danger/10 hover:text-danger"
                                                wire:click.stop="removeEvidence({{ $index }})" aria-label="Hapus file">
                                                <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path
                                                        d="M5.22 5.22a.75.75 0 0 1 1.06 0L10 8.94l3.72-3.72a.75.75 0 1 1 1.06 1.06L11.06 10l3.72 3.72a.75.75 0 1 1-1.06 1.06L10 11.06l-3.72 3.72a.75.75 0 1 1-1.06-1.06L8.94 10 5.22 6.28a.75.75 0 0 1 0-1.06Z" />
                                                </svg>
                                            </button>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        <div class="md:col-span-2 space-y-3 border-t border-border">
                            <flux:label>Verifikasi Keamanan (Anti-Bot)</flux:label>
                            <div class="flex flex-col gap-4 rounded-xl border border-warning/30 bg-warning/10 p-4 sm:flex-row sm:items-center">
                                <div class="flex shrink-0 items-center justify-center rounded-lg border-2 border-warning/40 bg-surface px-4 py-2 font-mono text-2xl font-black tracking-widest text-warning shadow-inner">
                                    {{ $captcha_val1 }} + {{ $captcha_val2 }}
                                </div>
                                <div class="flex-1">
                                    <flux:input wire:model="captcha_answer" placeholder="Berapa hasilnya?" class="w-full" />
                                </div>
                            </div>
                            @error('captcha_answer')
                                <span class="text-xs font-medium text-danger">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="md:col-span-2 mb-[10px]">
                        <p class="text-[12px] italic text-muted-foreground">
                            * Dengan menekan tombol Kirim, Anda menyatakan bahwa informasi yang diberikan adalah benar dan dapat dipertanggungjawabkan.
                        </p>
                    </div>

                        <div class="flex border-t pt-6">
                            <flux:spacer />
                            <flux:modal.close>
                                <flux:button variant="outline" class="cursor-pointer">Batal</flux:button>
                            </flux:modal.close>
                            <flux:button type="submit" variant="primary" class="ml-3 cursor-pointer" wire:loading.attr="disabled">
                                <span wire:loading.remove wire:target="submitIncident">Kirim</span>
                                <span wire:loading wire:target="submitIncident">Mengirim...</span>
                            </flux:button>
                        </div>
                    </form>
                </div>
            @endif
        </div>
    </flux:modal>
</section>
<style>
    ui-modal::backdrop,
    dialog::backdrop {
        background-color: rgba(24, 24, 27, 0.5) !important;
        backdrop-filter: blur(5px) !important;
        -webkit-backdrop-filter: blur(5px) !important;
    }

    dialog[data-modal="incident-modal"] {
        max-width: 900px !important;
        width: 90vw !important;
    }
</style>
