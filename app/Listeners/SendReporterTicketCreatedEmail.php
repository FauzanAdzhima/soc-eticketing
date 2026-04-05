<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Mail\ReporterTicketCreatedMail;
use Illuminate\Support\Facades\Mail;

class SendReporterTicketCreatedEmail
{
    public function handle(TicketCreated $event): void
    {
        $email = $event->ticket->reporter_email;

        if ($email === '' || $email === null) {
            return;
        }

        Mail::to($email)->queue(new ReporterTicketCreatedMail(
            $event->ticket,
            $event->reporterChatTokenPlain,
        ));
    }
}
