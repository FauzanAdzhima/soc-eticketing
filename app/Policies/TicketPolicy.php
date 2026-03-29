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

    public function view(User $user, Ticket $ticket): bool
    {
        if (! $user->can('ticket.view')) {
            return false;
        }

        if ($user->can('ticket.view_all')) {
            return true;
        }

        if ($user->hasRole('pic')) {
            return true;
        }

        if ($ticket->created_by === $user->id) {
            return true;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function updateStatus(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($ticket->status !== Ticket::STATUS_ON_PROGRESS) {
            return false;
        }

        if (! $user->can('ticket.respond')) {
            return false;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    public function close(User $user, Ticket $ticket): bool
    {
        if ($ticket->isClosed()) {
            return false;
        }

        if ($ticket->isReportRejected()) {
            return false;
        }

        return $user->can('ticket.close') && $user->hasRole('koordinator');
    }

    public function verifyReport(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if (! $user->hasRole('pic')) {
            return false;
        }

        return $ticket->report_status === Ticket::REPORT_STATUS_PENDING
            && $ticket->status === Ticket::STATUS_AWAITING_VERIFICATION;
    }

    public function rejectReport(User $user, Ticket $ticket): bool
    {
        return $this->verifyReport($user, $ticket);
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        if ($ticket->isTerminal()) {
            return false;
        }

        if ($user->hasRole('koordinator')) {
            return true;
        }

        if ($user->hasRole('pic')) {
            return $ticket->report_status === Ticket::REPORT_STATUS_VERIFIED
                && $ticket->status === Ticket::STATUS_OPEN;
        }

        return $ticket->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();
    }

    /**
     * PIC dapat memverifikasi laporan (Pending) atau menugaskan setelah Verified + Open.
     */
    public function interactAsPic(User $user, Ticket $ticket): bool
    {
        if (! $user->hasRole('pic')) {
            return false;
        }

        return $this->verifyReport($user, $ticket) || $this->assign($user, $ticket);
    }
}
