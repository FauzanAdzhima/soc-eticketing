<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentAnalysis extends Model
{
    protected $fillable = [
        'ticket_id',
        'performed_by',
        'severity',
        'impact',
        'root_cause',
        'recommendation',
        'analysis_result',
    ];

    /**
     * @return BelongsTo<Ticket, $this>
     */
    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function performer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'performed_by');
    }

    /**
     * @return HasMany<IncidentIoc, $this>
     */
    public function iocs(): HasMany
    {
        return $this->hasMany(IncidentIoc::class, 'analysis_id');
    }
}
