<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\IncidentAnalysis;
use App\Models\IncidentIocType;
use App\Models\Ticket;
use App\Models\User;
use App\Services\IncidentAnalysisService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class TicketAnalysisPage extends Component
{
    public string $ticketPublicId = '';

    public string $severity = 'Medium';

    public string $impact = '';

    public string $root_cause = '';

    public string $recommendation = '';

    public string $analysisSummary = '';

    /**
     * @var array<int, array{type_id: int|string|null, value: string, description: string}>
     */
    public array $iocRows = [];

    /** When true, always insert a new incident_analyses row instead of updating the analyst's latest entry. */
    public bool $saveAsAddendum = false;

    public function mount(Ticket $ticket): void
    {
        $this->authorize('analyze', $ticket);
        $this->ticketPublicId = $ticket->public_id;

        $latestOwn = IncidentAnalysis::query()
            ->where('ticket_id', $ticket->id)
            ->where('performed_by', Auth::id())
            ->with('iocs')
            ->latest()
            ->first();

        if ($latestOwn !== null) {
            $this->severity = (string) ($latestOwn->severity ?: 'Medium');
            $this->impact = (string) ($latestOwn->impact ?? '');
            $this->root_cause = (string) ($latestOwn->root_cause ?? '');
            $this->recommendation = (string) ($latestOwn->recommendation ?? '');
            $this->analysisSummary = (string) ($latestOwn->analysis_result ?? '');

            $rows = [];
            foreach ($latestOwn->iocs as $ioc) {
                $rows[] = [
                    'type_id' => $ioc->incident_ioc_type_id,
                    'value' => (string) ($ioc->value ?? ''),
                    'description' => (string) ($ioc->description ?? ''),
                ];
            }
            $this->iocRows = $rows !== [] ? $rows : [
                ['type_id' => null, 'value' => '', 'description' => ''],
            ];
        } else {
            $this->iocRows = [
                ['type_id' => null, 'value' => '', 'description' => ''],
            ];
        }
    }

    public function addIocRow(): void
    {
        $this->iocRows[] = ['type_id' => null, 'value' => '', 'description' => ''];
    }

    public function removeIocRow(int $index): void
    {
        unset($this->iocRows[$index]);
        $this->iocRows = array_values($this->iocRows);
        if ($this->iocRows === []) {
            $this->iocRows[] = ['type_id' => null, 'value' => '', 'description' => ''];
        }
    }

    public function submit(IncidentAnalysisService $service): void
    {
        $ticket = Ticket::query()->where('public_id', $this->ticketPublicId)->firstOrFail();
        $this->authorize('analyze', $ticket);

        $this->validateIocRowConsistency();

        $this->validate([
            'severity' => ['required', 'string', 'in:Low,Medium,High,Critical'],
            'impact' => ['required', 'string'],
            'root_cause' => ['required', 'string'],
            'recommendation' => ['required', 'string'],
            'analysisSummary' => ['nullable', 'string'],
        ], [], [
            'severity' => 'tingkat keparahan',
            'impact' => 'dampak',
            'root_cause' => 'akar masalah',
            'recommendation' => 'rekomendasi',
            'analysisSummary' => 'ringkasan analisis',
        ]);

        $normalizedIocs = $this->normalizedIocRows();
        foreach ($normalizedIocs as $i => $row) {
            $validator = validator(
                [
                    'type_id' => $row['type_id'],
                    'value' => $row['value'],
                    'description' => $row['description'],
                ],
                [
                    'type_id' => ['required', 'integer', 'exists:incident_ioc_types,id'],
                    'value' => ['required', 'string'],
                    'description' => ['nullable', 'string'],
                ],
                [],
                [
                    'type_id' => 'tipe IOC #'.($i + 1),
                    'value' => 'nilai IOC #'.($i + 1),
                ]
            );
            if ($validator->fails()) {
                foreach ($validator->errors()->messages() as $key => $msgs) {
                    $msg = $msgs[0] ?? '';
                    $originalIndex = $row['_original_index'] ?? $i;
                    $this->addError("iocRows.{$originalIndex}.{$key}", $msg);
                }
            }
        }

        if ($this->getErrorBag()->isNotEmpty()) {
            throw ValidationException::withMessages($this->getErrorBag()->toArray());
        }

        $payloadIocs = array_map(static function (array $row): array {
            return [
                'type_id' => $row['type_id'],
                'value' => $row['value'],
                'description' => $row['description'],
            ];
        }, $normalizedIocs);

        /** @var User $analyst */
        $analyst = User::query()->findOrFail((int) Auth::id());

        try {
            [, $persistMode] = $service->store($ticket, $analyst, [
                'severity' => $this->severity,
                'impact' => $this->impact,
                'root_cause' => $this->root_cause,
                'recommendation' => $this->recommendation,
                'analysis_result' => $this->analysisSummary !== '' ? $this->analysisSummary : null,
            ], $payloadIocs, $this->saveAsAddendum);
        } catch (\Throwable $e) {
            session()->flash('toast_error', $e->getMessage());

            return;
        }

        $message = match ($persistMode) {
            'updated' => 'Analisis dan IOC berhasil diperbarui.',
            'addendum' => 'Entri analisis tambahan (addendum) berhasil disimpan.',
            default => 'Analisis dan IOC berhasil disimpan.',
        };

        session()->flash('toast_success', $message);

        $this->redirect(route('tickets.index', [
            'scope' => 'analyst',
            'ticket' => $this->ticketPublicId,
        ]), navigate: true);
    }

    /**
     * @return list<array{type_id: int, value: string, description: string|null, _original_index: int}>
     */
    private function normalizedIocRows(): array
    {
        $out = [];
        foreach ($this->iocRows as $index => $row) {
            $value = trim((string) ($row['value'] ?? ''));
            if ($value === '') {
                continue;
            }
            $typeRaw = $row['type_id'] ?? null;
            if ($typeRaw === null || $typeRaw === '') {
                continue;
            }
            $desc = trim((string) ($row['description'] ?? ''));
            $out[] = [
                'type_id' => (int) $typeRaw,
                'value' => $value,
                'description' => $desc !== '' ? $desc : null,
                '_original_index' => $index,
            ];
        }

        return $out;
    }

    private function validateIocRowConsistency(): void
    {
        $messages = [];
        foreach ($this->iocRows as $i => $row) {
            $value = trim((string) ($row['value'] ?? ''));
            $typeRaw = $row['type_id'] ?? null;
            $hasType = $typeRaw !== null && $typeRaw !== '';

            if ($hasType && $value === '') {
                $messages["iocRows.{$i}.value"] = ['Lengkapi nilai IOC atau kosongkan tipe pada baris ini.'];
            }
            if ($value !== '' && ! $hasType) {
                $messages["iocRows.{$i}.type_id"] = ['Pilih tipe IOC untuk baris yang berisi nilai.'];
            }
        }

        if ($messages !== []) {
            throw ValidationException::withMessages($messages);
        }
    }

    public function render(): View
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->ticketPublicId)
            ->with([
                'category',
                'creator',
                'organization',
                'evidences',
                'assignments' => fn ($q) => $q->where('is_active', true)->with('user'),
                'analyses' => fn ($q) => $q
                    ->orderByDesc('created_at')
                    ->with([
                        'performer',
                        'iocs' => fn ($iq) => $iq->with('iocType'),
                    ]),
            ])
            ->firstOrFail();

        $this->authorize('analyze', $ticket);

        return view('livewire.pages.tickets.ticket-analysis-page', [
            'ticket' => $ticket,
            'iocTypes' => IncidentIocType::query()->orderBy('ioc_type')->get(),
            'hasExistingOwnAnalysis' => $ticket->analyses->contains(
                fn (IncidentAnalysis $a): bool => (int) $a->performed_by === (int) Auth::id()
            ),
        ]);
    }
}
