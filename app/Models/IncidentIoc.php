<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IncidentIoc extends Model
{
    protected $table = 'incident_ioc';

    protected $fillable = [
        'public_id',
        'analysis_id',
        'incident_ioc_type_id',
        'value',
        'description',
    ];

    /**
     * @return BelongsTo<IncidentAnalysis, $this>
     */
    public function analysis(): BelongsTo
    {
        return $this->belongsTo(IncidentAnalysis::class, 'analysis_id');
    }

    /**
     * @return BelongsTo<IncidentIocType, $this>
     */
    public function iocType(): BelongsTo
    {
        return $this->belongsTo(IncidentIocType::class, 'incident_ioc_type_id');
    }
}
