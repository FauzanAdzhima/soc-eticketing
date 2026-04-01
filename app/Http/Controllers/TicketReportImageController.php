<?php

namespace App\Http\Controllers;

use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Mime\MimeTypes;

class TicketReportImageController extends Controller
{
    public function store(Request $request, Ticket $ticket): JsonResponse
    {
        $this->authorize('manageIncidentReport', $ticket);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp,gif', 'max:5120'],
        ]);

        $file = $validated['image'];
        $extension = strtolower($file->getClientOriginalExtension()) ?: 'jpg';
        $directory = 'ticket-report-images/'.$ticket->public_id;
        $filename = Str::uuid()->toString().'.'.$extension;
        $path = $file->storeAs($directory, $filename, 'local');

        return response()->json([
            'url' => route('tickets.reports.images.show', [
                'ticket' => $ticket->public_id,
                'path' => $path,
            ]),
        ]);
    }

    public function show(Ticket $ticket, string $path): BinaryFileResponse
    {
        $this->authorize('manageIncidentReport', $ticket);

        $normalizedPath = ltrim(str_replace('\\', '/', $path), '/');
        $expectedPrefix = 'ticket-report-images/'.$ticket->public_id.'/';
        abort_unless(Str::startsWith($normalizedPath, $expectedPrefix), 404);

        $disk = Storage::disk('local');
        abort_unless($disk->exists($normalizedPath), 404);

        $fullPath = $disk->path($normalizedPath);
        $mime = MimeTypes::getDefault()->guessMimeType($fullPath) ?: 'application/octet-stream';

        $response = new BinaryFileResponse($fullPath, 200, [
            'Content-Type' => $mime,
        ], true);
        $response->setContentDisposition(ResponseHeaderBag::DISPOSITION_INLINE, basename($normalizedPath));

        return $response;
    }
}
