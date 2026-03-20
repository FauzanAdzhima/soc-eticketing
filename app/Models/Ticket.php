<?php

namespace App\Models;

use Exception;
use Illuminate\Database\Eloquent\Model;

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

        $this->status = $newStatus;
        $this->save();
    }
}
