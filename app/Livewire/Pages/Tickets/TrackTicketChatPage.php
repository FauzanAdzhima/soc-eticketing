<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class TrackTicketChatPage extends Component
{
    public Ticket $ticket;

    public string $token = '';

    public function mount(Ticket $ticket, string $token): void
    {
        $this->ticket = $ticket->loadMissing(['category', 'organization', 'evidences']);
        $this->token = $token;
    }

    public function render(): View
    {
        return view('livewire.pages.tickets.track-ticket-chat-page');
    }
}
