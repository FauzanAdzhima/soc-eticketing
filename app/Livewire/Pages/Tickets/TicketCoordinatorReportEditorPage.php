<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\Ticket;
use App\Models\TicketReport;
use App\Support\ReportHtmlSanitizer;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Arr;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class TicketCoordinatorReportEditorPage extends Component
{
    public string $ticketPublicId = '';

    public int $ticketReportId = 0;

    public string $bodyHtml = '';

    /** @var array<string, mixed> */
    public array $snapshotJson = [];

    public function mount(Ticket $ticket): void
    {
        $this->authorize('manageIncidentReport', $ticket);

        $ticket->load([
            'category',
            'creator',
            'organization',
            'evidences',
            'analyses' => fn ($q) => $q
                ->with([
                    'performer',
                    'iocs' => fn ($iq) => $iq->with('iocType'),
                ])
                ->orderByDesc('created_at'),
            'responseActions' => fn ($q) => $q
                ->with('performer')
                ->orderBy('created_at'),
        ]);

        $this->ticketPublicId = $ticket->public_id;

        $snapshot = $this->generateSnapshotJson($ticket);

        $existing = $ticket->ticketReport()->first();
        if ($existing === null) {
            $defaultBodyHtml = $this->generateBodyHtmlFromSnapshot($snapshot);

            $existing = TicketReport::create([
                'ticket_id' => $ticket->id,
                'status' => TicketReport::STATUS_DRAFT,
                'snapshot_json' => $snapshot,
                'body_markdown' => $defaultBodyHtml,
                'body_json' => null,
            ]);
        } else {
            if (empty($existing->snapshot_json) || ! is_array($existing->snapshot_json)) {
                // Only generate snapshot if it's missing. Snapshot is considered immutable afterwards.
                $existing->snapshot_json = $snapshot;
            }
        }

        $this->ticketReportId = (int) $existing->id;
        $this->snapshotJson = is_array($existing->snapshot_json ?? null) ? $existing->snapshot_json : [];

        $body = (string) ($existing->body_markdown ?? '');
        if ($body === '') {
            $snapshotForBody = $this->snapshotJson !== [] ? $this->snapshotJson : $snapshot;
            $body = $this->generateBodyHtmlFromSnapshot($snapshotForBody);
        }

        $this->bodyHtml = ReportHtmlSanitizer::sanitize($body);
    }

    public function saveDraft(): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->ticketPublicId)
            ->firstOrFail();

        $this->authorize('manageIncidentReport', $ticket);

        $ticketReport = TicketReport::query()
            ->where('ticket_id', $ticket->id)
            ->where('id', $this->ticketReportId)
            ->firstOrFail();

        $this->validate([
            'bodyHtml' => ['required', 'string', 'min:1', 'max:200000'],
        ], [], [
            'bodyHtml' => 'isi laporan',
        ]);

        $sanitizedBody = ReportHtmlSanitizer::sanitize($this->bodyHtml);

        $ticketReport->status = TicketReport::STATUS_DRAFT;
        $ticketReport->body_markdown = $sanitizedBody;
        $ticketReport->body_json = null;
        $ticketReport->save();
        $this->bodyHtml = $sanitizedBody;

        session()->flash('toast_success', 'Laporan koordinator tersimpan (draft).');
    }

    /**
     * Simpan draft lalu redirect ke halaman print-only.
     */
    public function exportPrint(): mixed
    {
        $this->saveDraft();

        return $this->redirect(route('tickets.reports.print', [
            'ticket' => $this->ticketPublicId,
            'report' => $this->ticketReportId,
        ]));
    }

    public function regenerateFromSnapshot(): void
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->ticketPublicId)
            ->firstOrFail();

        $this->authorize('manageIncidentReport', $ticket);

        $snapshot = $this->snapshotJson !== [] ? $this->snapshotJson : $this->generateSnapshotJson($ticket);
        if (! is_array($snapshot['ticket'] ?? null)) {
            $snapshot['ticket'] = [];
        }
        if (
            empty($snapshot['ticket']['closed_at'])
            && $ticket->closed_at !== null
        ) {
            $snapshot['ticket']['closed_at'] = $ticket->closed_at->toISOString();
        }
        $this->snapshotJson = $snapshot;

        $ticketReport = TicketReport::query()
            ->where('ticket_id', $ticket->id)
            ->where('id', $this->ticketReportId)
            ->first();
        if ($ticketReport !== null) {
            $ticketReport->snapshot_json = $snapshot;
            $ticketReport->save();
        }

        $this->bodyHtml = ReportHtmlSanitizer::sanitize($this->generateBodyHtmlFromSnapshot($snapshot));

        $this->dispatch(
            'ticket-report-editor-reset',
            ticketPublicId: $this->ticketPublicId,
            html: $this->bodyHtml
        );

        session()->flash('toast_success', 'Template laporan berhasil digenerate ulang dari snapshot tiket.');
    }

    public function render(): View
    {
        $ticket = Ticket::query()
            ->where('public_id', $this->ticketPublicId)
            ->with('evidences')
            ->firstOrFail();

        $ticketReport = TicketReport::query()
            ->where('ticket_id', $ticket->id)
            ->where('id', $this->ticketReportId)
            ->firstOrFail();

        return view('livewire.pages.tickets.ticket-coordinator-report-editor-page', [
            'ticket' => $ticket,
            'ticketReport' => $ticketReport,
            'previewHtml' => ReportHtmlSanitizer::sanitize($this->bodyHtml),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function generateSnapshotJson(Ticket $ticket): array
    {
        $evidences = $ticket->evidences->map(static function ($evidence): array {
            return [
                'original_name' => (string) ($evidence->original_name ?? ''),
                'mime_type' => (string) ($evidence->mime_type ?? ''),
                'size' => $evidence->size !== null ? (int) $evidence->size : null,
                'path' => (string) ($evidence->path ?? ''),
            ];
        })->values()->all();

        $analyses = $ticket->analyses->map(static function ($analysis): array {
            $iocs = $analysis->iocs->map(static function ($ioc): array {
                return [
                    'type' => (string) ($ioc->iocType?->ioc_type ?? ''),
                    'type_description' => $ioc->iocType?->description,
                    'value' => (string) ($ioc->value ?? ''),
                    'description' => $ioc->description,
                ];
            })->values()->all();

            return [
                'performed_by' => (string) ($analysis->performer?->name ?? ''),
                'severity' => $analysis->severity,
                'impact' => $analysis->impact,
                'root_cause' => $analysis->root_cause,
                'recommendation' => $analysis->recommendation,
                'analysis_result' => $analysis->analysis_result,
                'iocs' => $iocs,
            ];
        })->values()->all();

        $responseActions = $ticket->responseActions->map(static function ($action): array {
            $meta = $action->meta;
            $safeMeta = is_array($meta) && Arr::isAssoc($meta) ? $meta : null;

            return [
                'performed_by' => (string) ($action->performer?->name ?? ''),
                'action_type' => trim((string) ($action->action_type ?? '')),
                'description' => (string) ($action->description ?? ''),
                'meta' => $safeMeta,
                'created_at' => $action->created_at?->toISOString(),
            ];
        })->values()->all();

        return [
            'ticket' => [
                'ticket_number' => (string) ($ticket->ticket_number ?? ''),
                'title' => (string) ($ticket->title ?? ''),
                'reporter_name' => (string) ($ticket->reporter_name ?? ''),
                'reporter_email' => (string) ($ticket->reporter_email ?? ''),
                'reporter_phone' => $ticket->reporter_phone,
                'incident_category' => (string) ($ticket->category?->name ?? ''),
                'incident_severity' => $ticket->incident_severity,
                'incident_time' => $ticket->incident_time?->toISOString(),
                'incident_description' => $ticket->incident_description,
                'reported_at' => $ticket->reported_at?->toISOString(),
                'closed_at' => $ticket->closed_at?->toISOString(),
                'creator_name' => $ticket->creator?->name,
                'evidences' => $evidences,
            ],
            'analyses' => $analyses,
            'response_actions' => $responseActions,
        ];
    }

    private function generateBodyHtmlFromSnapshot(array $snapshot): string
    {
        $ticket = is_array($snapshot['ticket'] ?? null) ? $snapshot['ticket'] : [];
        $analyses = is_array($snapshot['analyses'] ?? null) ? $snapshot['analyses'] : [];
        $responseActions = is_array($snapshot['response_actions'] ?? null) ? $snapshot['response_actions'] : [];

        $html = '<h1>Laporan Insiden</h1>';
        $html .= '<ul>';
        $html .= '<li>No. Tiket: ' . e((string) ($ticket['ticket_number'] ?? '—')) . '</li>';
        $html .= '<li>Judul: ' . e((string) ($ticket['title'] ?? '—')) . '</li>';
        $html .= '<li>Pelapor: ' . e((string) ($ticket['reporter_name'] ?? '—')) . '</li>';
        $html .= '<li>Email: ' . e((string) ($ticket['reporter_email'] ?? '—')) . '</li>';
        if (! empty($ticket['reporter_phone'])) {
            $html .= '<li>Kontak: ' . e((string) ($ticket['reporter_phone'] ?? '')) . '</li>';
        }
        if (! empty($ticket['incident_category'])) {
            $html .= '<li>Kategori: ' . e((string) ($ticket['incident_category'] ?? '')) . '</li>';
        }
        if (! empty($ticket['incident_severity'])) {
            $html .= '<li>Keparahan: ' . e((string) ($ticket['incident_severity'] ?? '')) . '</li>';
        }
        if (! empty($ticket['incident_time'])) {
            $html .= '<li>Waktu kejadian: ' . e($this->formatSnapshotDateTime($ticket['incident_time'])) . '</li>';
        }
        if (! empty($ticket['closed_at'])) {
            $html .= '<li>Waktu ditutup: ' . e($this->formatSnapshotDateTime($ticket['closed_at'])) . '</li>';
        }
        $html .= '</ul>';

        $html .= '<h2>Deskripsi Insiden</h2>';
        $html .= '<p>' . nl2br(e((string) ($ticket['incident_description'] ?? '—'))) . '</p>';
        $html .= '<h2>Analisis Insiden</h2>';

        if ($analyses === []) {
            $html .= '<p>Tidak ada analisis.</p>';
        } else {
            foreach ($analyses as $index => $analysis) {
                $html .= '<h3>Analisis #' . ($index + 1) . '</h3>';
                $html .= '<ul>';
                $html .= '<li>Severity: ' . e((string) ($analysis['severity'] ?? '—')) . '</li>';
                if (! empty($analysis['impact'])) {
                    $html .= '<li>Dampak: ' . e((string) $analysis['impact']) . '</li>';
                }
                if (! empty($analysis['root_cause'])) {
                    $html .= '<li>Akar masalah: ' . e((string) $analysis['root_cause']) . '</li>';
                }
                if (! empty($analysis['recommendation'])) {
                    $html .= '<li>Rekomendasi: ' . e((string) $analysis['recommendation']) . '</li>';
                }
                $html .= '</ul>';
                if (! empty($analysis['analysis_result'])) {
                    $html .= '<p><strong>Ringkasan:</strong></p>';
                    $html .= '<p>' . nl2br(e((string) $analysis['analysis_result'])) . '</p>';
                }

                $iocs = is_array($analysis['iocs'] ?? null) ? $analysis['iocs'] : [];
                if ($iocs !== []) {
                    $html .= '<h4>IOC</h4><ul>';
                    foreach ($iocs as $ioc) {
                        $line = e((string) ($ioc['type'] ?? 'IOC')) . ': ' . e((string) ($ioc['value'] ?? ''));
                        if (! empty($ioc['description'])) {
                            $line .= ' (' . e((string) $ioc['description']) . ')';
                        }
                        $html .= '<li>' . $line . '</li>';
                    }
                    $html .= '</ul>';
                }
            }
        }

        $html .= '<h2>Tindakan Respons</h2>';
        if ($responseActions === []) {
            $html .= '<p>Tidak ada tindakan respons.</p>';
        } else {
            $html .= '<ul>';
            foreach ($responseActions as $action) {
                $html .= '<li>[' . e((string) ($action['action_type'] ?? 'action')) . '] ';
                $html .= e((string) ($action['description'] ?? '')) . '</li>';
            }
            $html .= '</ul>';
        }

        return $html;
    }

    private function formatSnapshotDateTime(mixed $value): string
    {
        $raw = (string) ($value ?? '');
        if ($raw === '') {
            return '—';
        }

        try {
            return Carbon::parse($raw)->timezone(config('app.timezone'))->format('d M Y H:i');
        } catch (\Throwable) {
            return $raw;
        }
    }
}

