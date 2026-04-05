<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.layout-main')]
class TicketChatPage extends Component
{
    public Ticket $ticket;

    public function mount(Ticket $ticket): void
    {
        $this->authorize('view', $ticket);
        $this->ticket = $ticket;
    }

    public function render(): View
    {
        return view('livewire.pages.tickets.ticket-chat-page');
    }
}
