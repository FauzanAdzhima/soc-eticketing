<?php

namespace App\Events;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketResolvedByResponder implements ShouldBroadcast
{
    use Dispatchable, \Illuminate\Broadcasting\InteractsWithSockets, SerializesModels;

    public function __construct(
        public Ticket $ticket,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        $coordinatorUserIds = User::role('koordinator')->pluck('id')->all();

        return array_map(
            fn (int $id): PrivateChannel => new PrivateChannel('user.'.$id),
            $coordinatorUserIds,
        );
    }

    public function broadcastAs(): string
    {
        return 'ticket.resolved';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'ticket_public_id' => $this->ticket->public_id,
            'ticket_number' => $this->ticket->ticket_number,
            'title' => $this->ticket->title,
        ];
    }
}

