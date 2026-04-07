<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketAssignment extends Model
{
    /** Primary handoff (e.g. PIC → analis); at most one active per ticket for this kind. */
    public const KIND_ASSIGNED_PRIMARY = 'assigned_primary';

    /** Additional contributor; many active rows allowed alongside primary. */
    public const KIND_CONTRIBUTOR = 'contributor';

    protected $fillable = [
        'ticket_id',
        'user_id',
        'kind',
        'assigned_at',
        'unassigned_at',
        'is_active',
    ];

    protected $casts = [
        'assigned_at' => 'datetime',
        'unassigned_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
