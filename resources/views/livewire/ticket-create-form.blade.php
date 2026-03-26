<section>
    <div wire:poll.60s="keepAlive" class="hidden"></div>

    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($categories as $category)
            <flux:card wire:click="openTicketForm({{ $category->id }})"
                class="cursor-pointer hover:ring-2 ring-white-500 transition-all">
                <flux:heading>{{ $category->name }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="incident-modal" class="md:w-[800px]">
        <div class="space-y-6">
            @if ($isSuccess)
                <div class="text-center py-10 animate-fade-in">
                    <flux:icon.check-circle variant="solid" class="size-16 text-green-500 mx-auto" />
                    <flux:heading size="xl" class="mt-4">Laporan Terkirim!</flux:heading>
                    <flux:subheading>Nomor Tiket Anda:</flux:subheading>
                    <div class="mt-4 p-4 bg-zinc-100 dark:bg-zinc-800 rounded-lg font-mono text-lg font-bold select-all">
                        {{ $createdTicketNo }}
                    </div>
                    <div class="mt-8">
                        <flux:button wire:click="closeSuccess" variant="primary">Tutup & Selesai</flux:button>
                    </div>
                </div>
            @else
                <header>
                    <flux:heading size="xl">Form Laporan Insiden</flux:heading>
                    <flux:subheading class="inline-flex items-center gap-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 px-3 py-1 rounded-lg text-sm">
                        Kategori: {{ $this->selectedCategory?->name ?? '' }}
                    </flux:subheading>
                </header>

                <form wire:submit="submitIncident" class="space-y-6">
                    <flux:input label="Subjek Aduan" wire:model.defer="formData.title"
                        placeholder="cont: Indikasi serangan pada sistem X" required />

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">
                        <flux:input label="Nama Lengkap" wire:model.defer="formData.reporter_name" icon="user"
                            placeholder="John Doe" required />

                        <flux:input label="No. WhatsApp/Telepon" wire:model.defer="formData.reporter_phone"
                            icon="phone" placeholder="081234567890" required />

                        <div class="p-4 rounded-lg border border-zinc-700 md:col-span-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
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

                        <div class="md:col-span-2 space-y-3 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:label>Bukti Dukung (Screenshot/Foto)</flux:label>
                            <div class="flex items-center gap-4">
                                <input type="file" wire:model="evidenceFiles" multiple
                                    class="block w-full text-sm text-zinc-500 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer bg-zinc-600 dark:bg-zinc-800 file:mr-4 file:py-2.5 file:px-4 file:rounded-l-lg file:border-0 file:text-sm file:font-semibold file:bg-zinc-600 file:text-zinc-700 hover:file:bg-zinc-600 dark:file:bg-zinc-700 dark:file:text-zinc-200" />
                                <div wire:loading wire:target="evidenceFiles" class="text-xs text-blue-600 animate-pulse">
                                    Mengunggah gambar...
                                </div>
                            </div>
                            @error('evidenceFiles.*')
                                <span class="text-red-500 text-xs font-medium">{{ $message }}</span>
                            @enderror

                            @if (!empty($evidenceFiles))
                                <div class="mt-2 grid grid-cols-2 md:grid-cols-4 gap-3">
                                    @foreach ($evidenceFiles as $index => $evidence)
                                        <div class="rounded border p-2 text-xs space-y-2" wire:key="evidence-preview-{{ $index }}">
                                            @if ($this->isImageFile($evidence))
                                                <img src="{{ $evidence->temporaryUrl() }}" alt="Preview evidence"
                                                    class="h-24 w-full object-cover rounded">
                                            @else
                                                <div class="h-24 w-full rounded bg-zinc-100 dark:bg-zinc-800 flex items-center justify-center text-zinc-500">
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

                        <div class="md:col-span-2 space-y-3 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:label>Verifikasi Keamanan (Anti-Bot)</flux:label>
                            <div class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-orange-50/50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/50 rounded-xl">
                                <div class="shrink-0 flex items-center justify-center bg-white dark:bg-zinc-800 border-2 border-orange-200 dark:border-orange-900 px-4 py-2 rounded-lg font-mono font-black text-2xl text-orange-600 tracking-widest shadow-inner">
                                    {{ $captcha_val1 }} + {{ $captcha_val2 }}
                                </div>
                                <div class="flex-1">
                                    <flux:input wire:model="captcha_answer" placeholder="Berapa hasilnya?" class="w-full" />
                                </div>
                            </div>
                            @error('captcha_answer')
                                <span class="text-red-500 text-xs font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="md:col-span-2 mb-[10px]">
                        <p class="text-[12px] text-zinc-500 italic">
                            * Dengan menekan tombol Kirim, Anda menyatakan bahwa informasi yang diberikan adalah benar dan dapat dipertanggungjawabkan.
                        </p>
                    </div>

                    <div class="flex border-t pt-6">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" class="ml-3 cursor-pointer" wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submitIncident">Kirim</span>
                            <span wire:loading wire:target="submitIncident">Mengirim...</span>
                        </flux:button>
                    </div>
                </form>
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
