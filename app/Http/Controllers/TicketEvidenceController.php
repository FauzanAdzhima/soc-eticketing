<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketEvidence;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;

class TicketEvidenceController extends Controller
{
    public function show(TicketEvidence $evidence): BinaryFileResponse
    {
        $this->authorize('view', $evidence->ticket);

        return $this->streamEvidence($evidence);
    }

    public function showGuest(Ticket $ticket, string $token, TicketEvidence $evidence): BinaryFileResponse
    {
        abort_unless($this->reporterTokenMatches($ticket, $token), 403);
        abort_unless((int) $evidence->ticket_id === (int) $ticket->id, 404);

        return $this->streamEvidence($evidence);
    }

    private function reporterTokenMatches(Ticket $ticket, string $plainToken): bool
    {
        $stored = $ticket->reporter_chat_token_hash;

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, hash('sha256', $plainToken));
    }

    private function streamEvidence(TicketEvidence $evidence): BinaryFileResponse
    {
        $disk = Storage::disk($evidence->disk);

        if (! $disk->exists($evidence->path)) {
            abort(404);
        }

        $fullPath = $disk->path($evidence->path);

        $mime = MimeTypes::getDefault()->guessMimeType($fullPath);
        if ($mime === null || $mime === '') {
            $mime = $evidence->mime_type ?: 'application/octet-stream';
        }

        if (
            ($mime === 'application/octet-stream' || $mime === 'application/x-empty')
            && filled($evidence->mime_type)
        ) {
            $mime = $evidence->mime_type;
        }

        $filename = $evidence->original_name ?: basename($evidence->path);

        $response = new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => $mime,
        ], true);

        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, $filename);

        return $response;
    }
}
