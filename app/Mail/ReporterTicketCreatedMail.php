<?php

namespace App\Mail;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ReporterTicketCreatedMail extends Mailable implements ShouldQueue
{
    use Queueable, SerializesModels;

    public function __construct(
        public Ticket $ticket,
        public string $reporterChatTokenPlain,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'Laporan tiket '.$this->ticket->ticket_number.' diterima — SOC eTicketing',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.reporter-ticket-created',
            with: [
                'reporterName' => $this->ticket->reporter_name,
                'ticketNumber' => $this->ticket->ticket_number,
                'title' => $this->ticket->title,
                'trackUrl' => route('tickets.track.chat', [
                    'ticket' => $this->ticket->public_id,
                    'token' => $this->reporterChatTokenPlain,
                ], absolute: true),
            ],
        );
    }
}
