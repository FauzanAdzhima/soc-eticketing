<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TicketLog extends Model
{
    protected $fillable = [
        'ticket_id',
        'user_id',
        'action',
        'data',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
