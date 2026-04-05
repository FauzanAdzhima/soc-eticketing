<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TicketMessage extends Model
{
    public const VISIBILITY_INTERNAL = 'internal';

    public const VISIBILITY_EXTERNAL = 'external';

    public $timestamps = false;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'ticket_id',
        'user_id',
        'guest_name',
        'visibility',
        'message',
        'attachment_path',
        'attachment_original_name',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (TicketMessage $message): void {
            if ($message->created_at === null) {
                $message->created_at = now();
            }
        });
    }

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeExternal(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_EXTERNAL);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeInternal(Builder $query): Builder
    {
        return $query->where('visibility', self::VISIBILITY_INTERNAL);
    }

    /**
     * Messages visible to a guest with a valid reporter token (external only).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeVisibleToGuest(Builder $query): Builder
    {
        return $query->external();
    }

    /**
     * Eager-load sender and Spatie roles for chat UI (avoids N+1 on role badges).
     *
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeWithSenderRoles(Builder $query): Builder
    {
        return $query->with(['user.roles']);
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeForTicket(Builder $query, int $ticketId): Builder
    {
        return $query->where('ticket_id', $ticketId);
    }

    /**
     * Preview kind for safe inline display (extension-based; stream still validates MIME).
     */
    public function attachmentPreviewKind(): ?string
    {
        $name = $this->attachment_original_name ?: $this->attachment_path;
        if (! is_string($name) || $name === '') {
            return null;
        }

        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));

        if (in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return 'image';
        }

        if ($ext === 'pdf') {
            return 'pdf';
        }

        return null;
    }
}
