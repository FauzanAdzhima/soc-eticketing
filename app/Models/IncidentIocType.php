<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class IncidentIocType extends Model
{
    protected $fillable = [
        'ioc_type',
        'description',
    ];

    /**
     * @return HasMany<IncidentIoc, $this>
     */
    public function iocs(): HasMany
    {
        return $this->hasMany(IncidentIoc::class, 'incident_ioc_type_id');
    }
}
