<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TicketAssigned
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public $ticket;
    public $userId; // Assigned user
    public $assignedBy; // User who assigned the ticket

    /**
     * Create a new event instance.
     */
    public function __construct($ticket, $userId, $assignedBy)
    {
        $this->ticket = $ticket;
        $this->userId = $userId;
        $this->assignedBy = $assignedBy;
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return array<int, Channel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('channel-name'),
        ];
    }
}
