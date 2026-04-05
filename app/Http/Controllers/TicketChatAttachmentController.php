<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use App\Models\TicketMessage;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;

class TicketChatAttachmentController extends Controller
{
    public function showStaff(Ticket $ticket, TicketMessage $message): BinaryFileResponse
    {
        $this->authorize('view', $ticket);
        $this->assertMessageBelongsToTicketWithAttachment($ticket, $message);

        return $this->streamAttachment($message);
    }

    public function showGuest(Ticket $ticket, string $token, TicketMessage $message): BinaryFileResponse
    {
        if (! $this->reporterTokenMatches($ticket, $token)) {
            abort(403);
        }

        abort_unless($message->visibility === TicketMessage::VISIBILITY_EXTERNAL, 403);
        $this->assertMessageBelongsToTicketWithAttachment($ticket, $message);

        return $this->streamAttachment($message);
    }

    private function assertMessageBelongsToTicketWithAttachment(Ticket $ticket, TicketMessage $message): void
    {
        abort_unless((int) $message->ticket_id === (int) $ticket->id, 404);

        $path = $message->attachment_path;
        abort_unless(is_string($path) && $path !== '', 404);
    }

    private function reporterTokenMatches(Ticket $ticket, string $plainToken): bool
    {
        $stored = $ticket->reporter_chat_token_hash;

        if (! is_string($stored) || $stored === '') {
            return false;
        }

        return hash_equals($stored, hash('sha256', $plainToken));
    }

    private function streamAttachment(TicketMessage $message): BinaryFileResponse
    {
        $disk = Storage::disk('public');

        if (! $disk->exists($message->attachment_path)) {
            abort(404);
        }

        $fullPath = $disk->path($message->attachment_path);
        $mime = MimeTypes::getDefault()->guessMimeType($fullPath) ?: 'application/octet-stream';
        $filename = $message->attachment_original_name ?: basename((string) $message->attachment_path);

        $response = new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => $mime,
        ], true);

        $disposition = request()->query('disposition') === 'attachment'
            ? ResponseHeaderBag::DISPOSITION_ATTACHMENT
            : ResponseHeaderBag::DISPOSITION_INLINE;

        $response->setContentDisposition($disposition, $filename);

        return $response;
    }
}
