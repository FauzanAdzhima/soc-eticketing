<?php

namespace App\Livewire;

use App\Models\IncidentCategory;
use App\Models\Organization;
use App\Services\TicketService;
use Livewire\Component;
use Livewire\WithFileUploads;

class TicketCreateForm extends Component
{
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

    public function mount(): void
    {
        $this->formData = $this->defaultFormData();
        $this->categories = IncidentCategory::all();
        $this->organizations = Organization::orderBy('name')->get();
        $this->generateCaptcha();
    }

    public function render()
    {
        return view('livewire.ticket-create-form');
    }

    public function generateCaptcha(): void
    {
        $this->captcha_val1 = rand(1, 10);
        $this->captcha_val2 = rand(1, 10);
        $this->captcha_answer = '';
    }

    public function openTicketForm(int $categoryId): void
    {
        $this->selectedCategoryId = $categoryId;
        $this->isSuccess = false;
        $this->modal('incident-modal')->show();
    }

    public function submitIncident(TicketService $ticketService): void
    {
        $this->validate();

        $hasOrgId = filled($this->formData['reporter_organization_id'] ?? null);
        $hasOrgName = filled($this->formData['reporter_organization_name'] ?? null);
        if ($hasOrgId === $hasOrgName) {
            $this->addError('formData.reporter_organization_id', 'Pilih salah satu jenis organisasi pelapor.');
            return;
        }

        if ((int) $this->captcha_answer !== $this->captcha_val1 + $this->captcha_val2) {
            $this->addError('captcha_answer', 'Jawaban Captcha salah.');
            return;
        }

        $ticket = $ticketService->createTicket(array_merge($this->formData, [
            'incident_category_id' => $this->selectedCategoryId,
            'evidence_files' => $this->evidenceFiles,
        ]));

        $this->isSuccess = true;
        $this->createdTicketNo = $ticket->ticket_number;
    }

    public function closeSuccess(): void
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

    public function keepAlive(): void
    {
        // Keep session alive while long form is filled.
    }

    public function isImageFile($file): bool
    {
        try {
            if (!is_object($file) || !method_exists($file, 'getMimeType')) {
                return false;
            }

            return str_starts_with((string) $file->getMimeType(), 'image/');
        } catch (\Throwable $e) {
            return false;
        }
    }

    public function evidenceOriginalName($file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getClientOriginalName')) {
                return (string) $file->getClientOriginalName();
            }
        } catch (\Throwable $e) {
            // Ignore invalid temporary upload state.
        }

        return 'Lampiran';
    }

    public function evidenceSizeKb($file): string
    {
        try {
            if (is_object($file) && method_exists($file, 'getSize')) {
                $size = (int) ($file->getSize() ?? 0);
                return number_format($size / 1024, 1);
            }
        } catch (\Throwable $e) {
            // Ignore invalid temporary upload state.
        }

        return '0.0';
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
}
