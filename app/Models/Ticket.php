<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;
use App\Models\TicketLog;
use App\Models\TicketAssignment;
use App\Models\TicketEvidence;
use App\Events\TicketAssigned;
use Illuminate\Support\Facades\Log;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'public_id',
        'ticket_number',
        'title',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'reporter_organization_id',
        'reporter_organization_name',
        'reported_at',
        'report_status',
        'report_is_valid',
        'incident_time',
        'incident_category_id', // Make sure this matches your foreign key
        'incident_severity',   // The only severity column we keep
        'incident_description',
        'status',
        'sub_status',
        'created_by',
        'closed_at',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'incident_time' => 'datetime',
        'report_is_valid' => 'boolean',
        'closed_at' => 'datetime',
    ];

    /**
     * Relationship with Organization
     */
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'reporter_organization_id');
    }

    public function category()
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id');
    }

    public function evidences()
    {
        return $this->hasMany(TicketEvidence::class);
    }

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

    public function updateStatus($newStatus, $user, $isSystem = false)
    {
        if (!$this->canTransitionTo($newStatus)) {
            throw new Exception("Invalid status transition");
        }

        $roleMap = [
            'triaged' => 'analis',
            'analyzed' => 'responder',
            'responded' => 'responder',
            'closed' => 'koordinator',
        ];

        if (
            !$isSystem &&
            isset($roleMap[$newStatus]) &&
            !$user->hasRole($roleMap[$newStatus])
        ) {
            throw new Exception("Unauthorized role");
        }

        if (!$isSystem) {
            $assigned = $this->assignments()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (!$assigned) {
                throw new Exception("You are not assigned to this ticket");
            }
        }

        $oldStatus = $this->status;

        $this->status = $newStatus;
        $this->save();

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id,
            'action' => $isSystem ? 'auto_status_updated' : 'status_updated',
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
