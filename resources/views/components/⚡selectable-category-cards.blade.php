<?php

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Models\Report;
use Livewire\Component;
use Illuminate\Support\Str;

new class extends Component {
    public $selectedCategory = null;
    public bool $isOfficialEmployee = false; // Toggle state

    public $formData = [
        'reporter_name' => '',
        'reporter_email' => '',
        'reporter_phone' => '',
        'reporter_organization_id' => null,
        'incident_title' => '',
        'incident_severity' => 'Low',
        'incident_description' => '',
    ];

    public function with(): array
    {
        return [
            'categories' => IncidentCategory::all(),
            'organizations' => Organization::orderBy('name')->get(),
        ];
    }

    public function openTicketForm(IncidentCategory $category)
    {
        $this->selectedCategory = $category;
        $this->modal('incident-modal')->show();
    }

    public function submitIncident()
    {
        // Validation logic here...

        Report::create([
            'public_id' => (string) Str::uuid(),
            'reporter_name' => $this->formData['reporter_name'],
            'reporter_email' => $this->formData['reporter_email'],
            'reporter_phone' => $this->formData['reporter_phone'],
            'reporter_organization_id' => $this->isOfficialEmployee ? $this->formData['reporter_organization_id'] : null,
            'incident_title' => $this->formData['incident_title'],
            'incident_category' => $this->selectedCategory->name,
            'incident_severity' => $this->formData['incident_severity'],
            'incident_description' => $this->formData['incident_description'],
            'reported_at' => now(),
        ]);

        $this->modal('incident-modal')->close();
        flux()->toast('Laporan Insiden Berhasil Dikirim.');
    }
}; ?>

<section>
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        @foreach ($categories as $category)
            <flux:card wire:click="openTicketForm({{ $category->id }})" class="cursor-pointer hover:ring-2 ring-blue-500">
                <flux:heading>{{ $category->name }}</flux:heading>
            </flux:card>
        @endforeach
    </div>

    <flux:modal name="incident-modal" class="md:w-[1000px]">
        <div class="space-y-6">
            <header>
                <flux:heading size="lg">Form Laporan Insiden</flux:heading>
                <flux:subheading>Kategori: {{ $selectedCategory->name ?? '' }}</flux:subheading>
            </header>

            <form wire:submit="submitIncident" class="space-y-6">
                <flux:input label="Subjek Aduan" wire:model="formData.incident_title" placeholder="cont: Indikasi serangan pada sistem X" required />

                <div class="grid grid-cols-1 md:grid-cols-2 gap-x-8 gap-y-6">

                    <flux:input label="Nama Lengkap" wire:model.defer="formData.reporter_name" icon="user" placeholder="John Doe"
                        required />

                    <flux:input type="email" label="Email {{ $isOfficialEmployee ? 'Dinas' : '' }}"
                        wire:model.defer="formData.reporter_email" icon="envelope" placeholder="{{ $isOfficialEmployee ? 'john.doe@kepriprov.go.id' : 'john.doe@org.com' }}" required />

                    <div class="p-4 rounded-lg border border-zinc-700 md:col-span-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                        <flux:checkbox wire:model.live="isOfficialEmployee"
                            label="Saya adalah pegawai / ASN Pemprov Kepri" />
                    </div>

                    <div @class(['md:col-span-2' => !$isOfficialEmployee])>
                        <flux:input label="No. WhatsApp/Telepon" wire:model.defer="formData.reporter_phone"
                            icon="phone" placeholder="081234567890" />
                    </div>

                    @if ($isOfficialEmployee)
                        <div wire:key="org-select-container">
                            <div wire:loading wire:target="isOfficialEmployee"
                                class="flex items-center gap-2 text-blue-600 animate-pulse pt-4">
                                <flux:icon.arrow-path class="size-4 animate-spin" />
                                <span class="text-xs font-medium">Memuat daftar instansi...</span>
                            </div>

                            <div wire:loading.remove wire:target="isOfficialEmployee">
                                <flux:select label="Pilih Instansi/Organisasi"
                                    wire:model="formData.reporter_organization_id" icon="building-office" searchable
                                    placeholder="Cari nama dinas...">
                                    @foreach ($organizations as $org)
                                        <flux:select.option value="{{ $org->id }}">{{ $org->name }}
                                        </flux:select.option>
                                    @endforeach
                                </flux:select>
                            </div>
                        </div>
                    @endif

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
                </div>

                <div class="flex border-t pt-6">
                    <flux:spacer />
                    <flux:modal.close>
                        <flux:button variant="ghost">Batal</flux:button>
                    </flux:modal.close>
                    <flux:button type="submit" variant="primary" class="ml-3">Kirim Laporan</flux:button>
                </div>
            </form>
        </div>
    </flux:modal>
</section>
