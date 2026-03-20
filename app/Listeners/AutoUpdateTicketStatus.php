<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;

class AutoUpdateTicketStatus
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(TicketAssigned $event): void
    {
        $ticket = $event->ticket;
        $user = User::find($event->userId);

        if (!$user) {
            return;
        }

        // mapping role → status
        $mapping = [
            'analis' => 'triaged',
            'responder' => 'analyzed',
        ];

        foreach ($user->getRoleNames() as $role) {
            if (isset($mapping[$role])) {

                $newStatus = $mapping[$role];

                // validasi state machine
                if (!$ticket->canTransitionTo($newStatus)) {
                    return;
                }

                // update status
                $ticket->updateStatus($newStatus, $event->assignedBy);

                return;
            }
        }
    }
}
