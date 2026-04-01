<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketReport extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_PUBLISHED = 'published';

    protected $fillable = [
        'ticket_id',
        'status',
        'snapshot_json',
        'body_markdown',
        'body_json',
    ];

    protected $casts = [
        'snapshot_json' => 'array',
        'body_json' => 'array',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}

