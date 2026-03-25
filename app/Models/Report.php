<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    public function organization()
    {
        return $this->belongsTo(Organization::class, 'reporter_organization_id');
    }
}
