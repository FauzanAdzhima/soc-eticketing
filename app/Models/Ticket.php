<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use App\Models\TicketLog;
use App\Models\TicketAssignment;
use App\Events\TicketAssigned;

class Ticket extends Model
{
    protected $fillable = [
        'public_id',
        'title',
        'description',
        // 'created_by',
    ];

    const STATUS_OPEN = 'open';
    const STATUS_TRIAGED = 'triaged';
    const STATUS_ANALYZED = 'analyzed';
    const STATUS_RESPONDED = 'responded';
    const STATUS_CLOSED = 'closed';

    public function canTransitionTo($newStatus)
    {
        $allowed = [
            'open' => ['triaged'],
            'triaged' => ['analyzed'],
            'analyzed' => ['responded'],
            'responded' => ['closed'],
            'closed' => []
        ];

        return in_array($newStatus, $allowed[$this->status] ?? []);
    }

    public function updateStatus($newStatus, $user)
    {
        // validasi transisi
        if (!$this->canTransitionTo($newStatus)) {
            throw new Exception("Invalid status transition");
        }

        // validasi role
        $roleMap = [
            'triaged' => 'pic',
            'analyzed' => 'analis',
            'responded' => 'responder',
            'closed' => 'koordinator',
        ];

        if (isset($roleMap[$newStatus]) && !$user->hasRole($roleMap[$newStatus])) {
            throw new Exception("Unauthorized role");
        }

        // validasi assignment
        $assigned = $this->assignments()
            ->where('user_id', $user->id)
            ->where('is_active', true)
            ->exists();

        if (!$assigned) {
            throw new Exception("You are not assigned to this ticket");
        }

        // simpan status lama
        $oldStatus = $this->status;

        // update status
        $this->status = $newStatus;
        $this->save();

        // simpan log
        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id ?? null,
            'action' => 'status_updated',
            'data' => json_encode([
                'from' => $oldStatus,
                'to' => $newStatus
            ])
        ]);
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function assignTo($userId, $assignedBy)
    {
        // Nonaktifkan assignment lama
        $this->assignments()
            ->where('is_active', true)
            ->update([
                'is_active' => false,
                'unassigned_at' => now()
            ]);

        // Buat assignment baru
        $assignment = TicketAssignment::create([
            'ticket_id' => $this->id,
            'user_id' => $userId,
            'assigned_at' => now(),
            'is_active' => true
        ]);

        // Simpan log
        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $assignedBy->id ?? null,
            'action' => 'assigned',
            'data' => json_encode([
                'assigned_to' => $userId
            ])
        ]);

        event(new TicketAssigned($this, $userId, $assignedBy));

        return $assignment;
    }
}
