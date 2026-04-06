<?php

namespace App\Livewire\Pages\Tickets;

use App\Models\Ticket;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Attributes\Layout;
use Livewire\Component;

#[Layout('layouts.public')]
class TrackTicketSearchPage extends Component
{
    public string $ticket_reference = '';

    public string $token = '';

    public function lookup(): mixed
    {
        $this->validate([
            'ticket_reference' => ['required', 'string', 'max:255'],
            'token' => ['required', 'string', 'max:128'],
        ]);

        $limitKey = 'track-ticket-search:'.request()->ip();
        if (RateLimiter::tooManyAttempts($limitKey, 30)) {
            $this->addError('lookup', 'Terlalu banyak percobaan. Tunggu sebentar lalu coba lagi.');

            return null;
        }

        RateLimiter::hit($limitKey, 60);

        $ref = trim($this->ticket_reference);
        $plain = trim($this->token);

        $ticket = Ticket::query()
            ->where(function ($q) use ($ref) {
                $q->where('ticket_number', $ref)
                    ->orWhere('public_id', $ref);
            })
            ->first();

        $valid = $ticket
            && is_string($ticket->reporter_chat_token_hash)
            && $ticket->reporter_chat_token_hash !== ''
            && hash_equals($ticket->reporter_chat_token_hash, hash('sha256', $plain));

        if (! $valid) {
            $this->addError('lookup', 'Nomor tiket atau token tidak valid. Periksa kembali lalu coba lagi.');

            return null;
        }

        return $this->redirect(route('tickets.track.chat', [
            'ticket' => $ticket->public_id,
            'token' => $plain,
        ]));
    }

    public function render(): View
    {
        return view('livewire.pages.tickets.track-ticket-search-page');
    }
}
