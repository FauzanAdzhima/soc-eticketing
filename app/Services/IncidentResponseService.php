<?php

namespace App\Services;

use App\Models\IncidentResponseAction;
use App\Models\Ticket;
use App\Models\TicketLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use InvalidArgumentException;

class IncidentResponseService
{
    /**
     * @param  array<string, mixed>|null  $meta
     */
    public function storeAction(
        Ticket $ticket,
        User $user,
        string $actionType,
        string $description,
        ?array $meta = null,
    ): IncidentResponseAction {
        Gate::forUser($user)->authorize('recordResponseAction', $ticket);

        if (! in_array($actionType, IncidentResponseAction::allowedTypes(), true)) {
            throw new InvalidArgumentException('Jenis tindakan respons tidak valid.');
        }

        $description = trim($description);
        if ($description === '') {
            throw new InvalidArgumentException('Deskripsi tindakan wajib diisi.');
        }

        $subStatusBefore = $ticket->sub_status;

        return DB::transaction(function () use ($ticket, $user, $actionType, $description, $meta, $subStatusBefore): IncidentResponseAction {
            $action = IncidentResponseAction::query()->create([
                'ticket_id' => $ticket->id,
                'performed_by' => $user->id,
                'action_type' => $actionType,
                'description' => $description,
                'meta' => $meta,
            ]);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'response_action_recorded',
                'data' => json_encode([
                    'type' => $actionType,
                    'incident_response_action_id' => $action->id,
                    'sub_status_before' => $subStatusBefore,
                    'sub_status_after' => $ticket->fresh()?->sub_status ?? $subStatusBefore,
                ]),
            ]);

            return $action;
        });
    }

    public function markResponseResolved(Ticket $ticket, User $user): void
    {
        Gate::forUser($user)->authorize('markResponseResolved', $ticket);

        if ($ticket->sub_status !== Ticket::SUB_STATUS_RESPONSE) {
            throw new InvalidArgumentException('Penandaan selesai hanya saat sub-status Respons.');
        }

        if (! $ticket->responseActions()->exists()) {
            throw new InvalidArgumentException('Catat minimal satu tindakan respons sebelum menandai selesai.');
        }

        DB::transaction(function () use ($ticket, $user): void {
            $ticket->updateSubStatus(Ticket::SUB_STATUS_RESOLUTION, $user);

            TicketLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $user->id,
                'action' => 'response_marked_resolved',
                'data' => json_encode([
                    'sub_status' => Ticket::SUB_STATUS_RESOLUTION,
                ]),
            ]);
        });
    }
}
