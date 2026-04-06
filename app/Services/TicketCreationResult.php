<?php

namespace App\Services;

use App\Models\Ticket;

final readonly class TicketCreationResult
{
    public function __construct(
        public Ticket $ticket,
        public string $reporterChatTokenPlain,
    ) {}
}
