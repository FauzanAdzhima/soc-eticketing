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

    /**
     * Whether this file should be shown as an image thumbnail (MIME and/or extension).
     */
    public function isLikelyImage(): bool
    {
        if (filled($this->mime_type) && str_starts_with((string) $this->mime_type, 'image/')) {
            return true;
        }

        $name = $this->original_name ?: basename((string) $this->path);
        $ext = strtolower((string) pathinfo($name, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp', 'svg', 'avif'], true);
    }
}
