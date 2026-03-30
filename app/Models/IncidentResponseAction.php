<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentResponseAction extends Model
{
    public const TYPE_MITIGATION = 'mitigation';

    public const TYPE_ERADICATION = 'eradication';

    public const TYPE_RECOVERY = 'recovery';

    /**
     * @var list<string>
     */
    public static function allowedTypes(): array
    {
        return [
            self::TYPE_MITIGATION,
            self::TYPE_ERADICATION,
            self::TYPE_RECOVERY,
        ];
    }

    protected $fillable = [
        'ticket_id',
        'performed_by',
        'action_type',
        'description',
        'meta',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    /**
     * @param  \Illuminate\Database\Eloquent\Builder<\App\Models\IncidentResponseAction>  $query
     * @return \Illuminate\Database\Eloquent\Builder<\App\Models\IncidentResponseAction>
     */
    public function scopeOfType($query, string $actionType)
    {
        return $query->where('action_type', $actionType);
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }
}
