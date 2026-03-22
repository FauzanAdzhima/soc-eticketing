<?php

namespace App\Listeners;

use App\Events\TicketAssigned;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Models\User;
use Illuminate\Support\Facades\Log;

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
        $ticket = $event->ticket->fresh(); // 🔥 penting
        $user = User::find($event->userId);

        if (!$user) {
            Log::info('USER NOT FOUND');
            return;
        }

        $mapping = [
            'analis' => 'triaged',
            'responder' => 'analyzed',
        ];

        Log::info('AUTO WORKFLOW START', [
            'ticket_id' => $ticket->id,
            'current_status' => $ticket->status,
            'roles' => $user->getRoleNames(),
        ]);

        foreach ($user->getRoleNames() as $role) {
            if (isset($mapping[$role])) {

                $newStatus = $mapping[$role];

                Log::info('ROLE MATCHED', [
                    'role' => $role,
                    'target_status' => $newStatus
                ]);

                $can = $ticket->canTransitionTo($newStatus);

                Log::info('TRANSITION CHECK', [
                    'from' => $ticket->status,
                    'to' => $newStatus,
                    'can' => $can
                ]);

                if (!$can) {
                    return;
                }

                // 🔥 FORCE TEST
                $ticket->status = $newStatus;
                $ticket->save();

                Log::info('AFTER FORCE UPDATE', [
                    'status' => $ticket->fresh()->status
                ]);

                return;
            }
        }
    }
}
