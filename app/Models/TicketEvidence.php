<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TicketEvidence extends Model
{
    use HasFactory;

    protected $table = 'ticket_evidences';

    protected $fillable = [
        'ticket_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size',
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }
}
