<?php

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Services\TicketService;
use Livewire\Component;
use Livewire\WithFileUploads;

new class extends Component {
    use WithFileUploads;

    public ?int $selectedCategoryId = null;
    public bool $isOfficialEmployee = false;
    public bool $isSuccess = false;
    public string $createdTicketNo = '';
    public $categories = [];
    public $organizations = [];

    public $evidenceFiles = [];
    public $captcha_answer;
    public $captcha_val1;
    public $captcha_val2;

    public $formData = [];

    public function mount()
    {
        $this->formData = $this->defaultFormData();
        $this->categories = IncidentCategory::all();
        $this->organizations = Organization::orderBy('name')->get();
        $this->generateCaptcha();
    }

    public function generateCaptcha()
    {
        $this->captcha_val1 = rand(1, 10);
        $this->captcha_val2 = rand(1, 10);
        $this->captcha_answer = '';
    }

    public function openTicketForm(int $categoryId)
    {
        $this->selectedCategoryId = $categoryId;
        $this->isSuccess = false;
        $this->modal('incident-modal')->show();
    }

    public function submitIncident(TicketService $ticketService)
    {
        $this->validate();

        $hasOrgId = filled($this->formData['reporter_organization_id'] ?? null);
        $hasOrgName = filled($this->formData['reporter_organization_name'] ?? null);
        if ($hasOrgId === $hasOrgName) {
            $this->addError('formData.reporter_organization_id', 'Pilih salah satu jenis organisasi pelapor.');
            return;
        }

        // 1. Captcha Check
        if ((int) $this->captcha_answer !== $this->captcha_val1 + $this->captcha_val2) {
            $this->addError('captcha_answer', 'Jawaban Captcha salah.');
            return;
        }

        $ticket = $ticketService->createTicket(
            array_merge($this->formData, [
                'incident_category_id' => $this->selectedCategoryId,
                'evidence_files' => $this->evidenceFiles,
            ]),
        );

        $this->isSuccess = true;
        $this->createdTicketNo = $ticket->ticket_number;

        $this->dispatch('refresh');
    }

    // Add a reset method for when they close the success screen
    public function closeSuccess()
    {
        $this->modal('incident-modal')->close();
        $this->reset(['isSuccess', 'evidenceFiles', 'createdTicketNo', 'selectedCategoryId']);
        $this->formData = $this->defaultFormData();
        $this->generateCaptcha();
    }

    public function getSelectedCategoryProperty(): ?IncidentCategory
    {
        if (!$this->selectedCategoryId) {
            return null;
        }

        return IncidentCategory::find($this->selectedCategoryId);
    }

    protected function defaultFormData(): array
    {
        return [
            'title' => '',
            'reporter_name' => '',
            'reporter_email' => '',
            'reporter_phone' => '',
            'reporter_organization_id' => null,
            'reporter_organization_name' => '',
            'incident_severity' => 'Low',
            'incident_time' => now()->format('Y-m-d H:i'),
            'incident_description' => '',
        ];
    }

    protected function rules(): array
    {
        return [
            'selectedCategoryId' => 'required|exists:incident_categories,id',
            'formData.title' => 'required|string|max:255',
            'formData.reporter_name' => 'required|string|max:255',
            'formData.reporter_email' => 'required|email|max:255',
            'formData.reporter_phone' => 'nullable|string|max:30',
            'formData.reporter_organization_id' => 'nullable|exists:organizations,id',
            'formData.reporter_organization_name' => 'nullable|string|max:255',
            'formData.incident_severity' => 'required|in:Low,Medium,High,Critical',
            'formData.incident_time' => 'required|date',
            'formData.incident_description' => 'required|string',
            'evidenceFiles' => 'nullable|array',
            'evidenceFiles.*' => 'file|max:5120|mimes:jpg,jpeg,png,pdf,doc,docx,xls,xlsx,csv,txt,zip,rar',
        ];
    }

    public function evidenceOriginalName($file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                return (string) $file->getClientOriginalName();
            }
        } catch (\Throwable $e) {
            // Keep modal render stable even if temp upload object is invalid.
        }

        return 'Lampiran tidak terbaca';
    }

    public function evidenceSizeKb($file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getSize')) {
                $size = (int) ($file->getSize() ?? 0);
                return number_format($size / 1024, 1);
            }
        } catch (\Throwable $e) {
            // Keep modal render stable even if temp upload object is invalid.
        }

        return '0.0';
    }

    public function keepAlive(): void
    {
        // No-op. This keeps session and Livewire component state warm.
    }
};
?>

<section>
    <div wire:poll.60s="keepAlive" class="hidden"></div>

    {{-- Category Cards --}}
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($categories as $category)
            <flux:card wire:click="openTicketForm({{ $category->id }})"
                class="cursor-pointer hover:ring-2 ring-white-500 transition-all">
                <flux:heading>{{ $category->name }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    {{-- Report Form Modal --}}
    <flux:modal name="incident-modal" class="md:w-[800px]">
        <div class="space-y-6">
            @if ($isSuccess)
                <div class="text-center py-10 animate-fade-in text-zinc-900 dark:text-zinc-100">
                    <flux:icon.check-circle variant="solid"
                        class="size-16 mx-auto text-green-600 dark:text-green-400" />
                    <flux:heading size="xl" class="mt-4 text-zinc-900 dark:text-zinc-50">Laporan Terkirim!</flux:heading>
                    <flux:subheading class="text-zinc-600 dark:text-zinc-400">Nomor Tiket Anda:</flux:subheading>

                    <div
                        class="mt-4 rounded-lg border border-zinc-200 bg-zinc-100 p-4 font-mono text-lg font-bold select-all !text-zinc-900 dark:border-zinc-600 dark:bg-zinc-700 dark:!text-zinc-50">
                        {{ $createdTicketNo }}
                    </div>

                    <div class="mt-8">
                        <flux:button wire:click="closeSuccess" variant="primary">Tutup & Selesai</flux:button>
                    </div>
                </div>
            @else
                {{-- Modal Header --}}
                <header>
                    <flux:heading size="xl">Form Laporan Insiden</flux:heading>
                    <flux:subheading
                        class="inline-flex items-center gap-2 bg-zinc-100 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 px-3 py-1 rounded-lg text-sm">
                        Kategori: {{ $this->selectedCategory?->name ?? '' }}</flux:subheading>
                </header>

                {{-- Modal Form --}}
                <form wire:submit="submitIncident" class="space-y-6">
                    <flux:input label="Subjek Aduan" wire:model.defer="formData.title"
                        placeholder="cont: Indikasi serangan pada sistem X" required />

                    {{-- Nama Lengkap, Email, Kontak, Organisasi --}}
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                        <flux:input label="Nama Lengkap" wire:model.defer="formData.reporter_name" icon="user"
                            placeholder="John Doe" required />

                        <flux:input label="No. WhatsApp/Telepon" wire:model.defer="formData.reporter_phone"
                            icon="phone" placeholder="081234567890" required />

                        <div
                            class="p-4 rounded-lg border border-zinc-700 md:col-span-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                            <flux:checkbox wire:model.live="isOfficialEmployee"
                                label="Saya adalah pegawai / ASN Pemprov Kepri" />
                        </div>

                        <flux:input type="email" label="Email {{ $isOfficialEmployee ? 'Dinas' : '' }}"
                            wire:model.defer="formData.reporter_email" icon="envelope"
                            placeholder="{{ $isOfficialEmployee ? 'john.doe@kepriprov.go.id' : 'john.doe@org.com' }}"
                            required />

                        {{-- Conditional Organisasi --}}
                        <div wire:key="org-field-container">
                            @if ($isOfficialEmployee)
                                {{-- [ASN]: Searchable Select from Database --}}
                                <flux:select label="Instansi/Organisasi Pelapor"
                                    wire:model="formData.reporter_organization_id" icon="building-office" searchable>
                                    @foreach ($organizations as $org)
                                        <flux:select.option value="{{ $org->id }}">{{ $org->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            @else
                                {{-- [Public]: Manual Text Input --}}
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

                        {{-- Bukti Dukung --}}
                        <div class="md:col-span-2 space-y-3 border-t border-zinc-100 dark:border-zinc-800"
                            wire:key="evidence-upload-section">
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

                            {{-- Evidence Preview Box --}}
                            @if (!empty($evidenceFiles))
                                <div class="mt-2 grid grid-cols-1 md:grid-cols-2 gap-2">
                                    @foreach ($evidenceFiles as $index => $evidence)
                                        <div class="rounded border p-2 text-xs flex items-center justify-between gap-3"
                                            wire:key="evidence-preview-{{ $index }}">
                                            <div class="truncate">{{ $this->evidenceOriginalName($evidence) }}</div>
                                            <div class="text-zinc-400 shrink-0">
                                                {{ $this->evidenceSizeKb($evidence) }} KB
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>

                        {{-- Verifikasi Keamanan --}}
                        <div class="md:col-span-2 space-y-3 border-t border-zinc-100 dark:border-zinc-800">
                            <flux:label>Verifikasi Keamanan (Anti-Bot)</flux:label>

                            <div
                                class="flex flex-col sm:flex-row sm:items-center gap-4 p-4 bg-orange-50/50 dark:bg-orange-950/20 border border-orange-100 dark:border-orange-900/50 rounded-xl">
                                <div
                                    class="shrink-0 flex items-center justify-center bg-white dark:bg-zinc-800 border-2 border-orange-200 dark:border-orange-900 px-4 py-2 rounded-lg font-mono font-black text-2xl text-orange-600 tracking-widest shadow-inner">
                                    {{ $captcha_val1 }} + {{ $captcha_val2 }}
                                </div>

                                <div class="flex-1">
                                    <flux:input wire:model="captcha_answer" placeholder="Berapa hasilnya?"
                                        class="w-full" />

                                </div>
                            </div>

                            @error('captcha_answer')
                                <span class="text-red-500 text-xs font-medium">{{ $message }}</span>
                            @enderror
                        </div>
                    </div>

                    <div class="md:col-span-2 mb-[10px]">
                        <p class="text-[12px] text-zinc-500 italic">
                            * Dengan menekan tombol Kirim, Anda menyatakan bahwa informasi yang diberikan adalah benar
                            dan dapat dipertanggungjawabkan.
                        </p>
                    </div>

                    <div class="flex border-t pt-6">
                        <flux:spacer />
                        <flux:modal.close>
                            <flux:button variant="ghost" class="cursor-pointer">Batal</flux:button>
                        </flux:modal.close>
                        <flux:button type="submit" variant="primary" class="ml-3 cursor-pointer"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove wire:target="submitIncident">Kirim</span>
                            <span wire:loading wire:target="submitIncident">Mengirim...</span>
                        </flux:button>
                    </div>
                </form>
            @endif
        </div>
    </flux:modal>

</section>
