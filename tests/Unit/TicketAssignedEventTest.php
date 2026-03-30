<?php

namespace Tests\Unit;

use App\Events\TicketAssigned;
use App\Models\Ticket;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Tests\TestCase;

class TicketAssignedEventTest extends TestCase
{
    public function test_it_broadcasts_on_private_user_channel_with_payload(): void
    {
        $ticket = new Ticket([
            'public_id' => 'pub-1',
            'ticket_number' => 'T-1',
            'title' => 'Phishing',
        ]);

        $event = new TicketAssigned($ticket, 42, null);

        $this->assertInstanceOf(ShouldBroadcast::class, $event);

        $channels = $event->broadcastOn();
        $this->assertCount(1, $channels);
        $this->assertInstanceOf(PrivateChannel::class, $channels[0]);
        $this->assertSame('private-user.42', $channels[0]->name);

        $this->assertSame('ticket.assigned', $event->broadcastAs());
        $this->assertSame([
            'ticket_public_id' => 'pub-1',
            'ticket_number' => 'T-1',
            'title' => 'Phishing',
        ], $event->broadcastWith());
    }
}
