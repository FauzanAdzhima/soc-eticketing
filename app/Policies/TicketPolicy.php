<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->can('ticket.view');
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === 'closed') {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if ($ticket->status === 'closed') {
            return false;
        }

        if ($user->hasAnyRole(['pic', 'koordinator'])) {
            return true;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }
}
