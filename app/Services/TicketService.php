<?php

namespace App\Services;

use App\Models\Ticket;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class TicketService
{
    /**
     * Create a new class instance.
     */
    public function __construct()
    {
        //
    }

    public function createTicket(array $data): Ticket
    {
        return DB::transaction(function () use ($data) {
            $ticketNumber = $this->generateTicketNumber();

            $reporterOrganizationId = $data['reporter_organization_id'] ?? null;
            $reporterOrganizationName = $data['reporter_organization_name'] ?? null;

            // Official employee should bind to organization id only.
            if ($reporterOrganizationId) {
                $reporterOrganizationName = null;
            }

            $ticket = Ticket::create([
                'public_id' => (string) \Illuminate\Support\Str::uuid(),
                'ticket_number' => $ticketNumber,
                'title' => $data['title'],
                'reporter_name' => $data['reporter_name'],
                'reporter_email' => $data['reporter_email'],
                'reporter_phone' => $data['reporter_phone'] ?? null,
                'reporter_organization_id' => $reporterOrganizationId,
                'reporter_organization_name' => $reporterOrganizationName,
                'incident_category_id' => $data['incident_category_id'],
                'incident_severity' => $data['incident_severity'] ?? 'Low',
                'incident_description' => $data['incident_description'],
                'incident_time' => \Carbon\Carbon::parse($data['incident_time']),
                'reported_at' => now(),
                'status' => 'open',
                'created_by' => $data['created_by'] ?? null,
            ]);

            $evidenceFiles = $data['evidence_files'] ?? [];
            $this->storeTicketEvidence($ticket, $evidenceFiles);

            return $ticket->load('evidences');
        });
    }

    protected function storeTicketEvidence(Ticket $ticket, array $evidenceFiles): void
    {
        foreach ($evidenceFiles as $file) {
            if (!$file instanceof UploadedFile) {
                continue;
            }

            $path = $file->store('ticket-evidence', 'public');

            $ticket->evidences()->create([
                'disk' => 'public',
                'path' => $path,
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'size' => $file->getSize(),
            ]);
        }
    }

    protected function generateTicketNumber(): string
    {
        return 'TIC-' . now()->format('ym') . '-' . strtoupper(\Illuminate\Support\Str::random(4));
    }
}
