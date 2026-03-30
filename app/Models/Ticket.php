<?php

namespace App\Models;

use App\Events\TicketAssigned;
use Exception;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Ticket extends Model
{
    use HasFactory, SoftDeletes;

    /**
     * @var array<int, string>
     */
    protected $fillable = [
        'public_id',
        'ticket_number',
        'title',
        'reporter_name',
        'reporter_email',
        'reporter_phone',
        'reporter_organization_id',
        'reporter_organization_name',
        'reported_at',
        'report_status',
        'report_is_valid',
        'report_rejection_reason',
        'incident_time',
        'incident_category_id',
        'incident_severity',
        'incident_description',
        'status',
        'sub_status',
        'created_by',
        'closed_at',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'reported_at' => 'datetime',
        'incident_time' => 'datetime',
        'report_is_valid' => 'boolean',
        'closed_at' => 'datetime',
    ];

    public function organization()
    {
        return $this->belongsTo(Organization::class, 'reporter_organization_id');
    }

    public function category()
    {
        return $this->belongsTo(IncidentCategory::class, 'incident_category_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function getRouteKeyName(): string
    {
        return 'public_id';
    }

    public function evidences()
    {
        return $this->hasMany(TicketEvidence::class);
    }

    public function analyses()
    {
        return $this->hasMany(IncidentAnalysis::class);
    }

    public function responseActions()
    {
        return $this->hasMany(IncidentResponseAction::class);
    }

    public function logs()
    {
        return $this->hasMany(TicketLog::class);
    }

    public const STATUS_AWAITING_VERIFICATION = 'Awaiting Verification';

    public const STATUS_OPEN = 'Open';

    public const STATUS_ON_PROGRESS = 'On Progress';

    public const STATUS_CLOSED = 'Closed';

    public const SUB_STATUS_TRIAGE = 'Triage';

    public const SUB_STATUS_ANALYSIS = 'Analysis';

    public const SUB_STATUS_RESPONSE = 'Response';

    public const SUB_STATUS_RESOLUTION = 'Resolution';

    public const REPORT_STATUS_PENDING = 'Pending';

    public const REPORT_STATUS_VERIFIED = 'Verified';

    public const REPORT_STATUS_REJECTED = 'Rejected';

    /** Tiket dihentikan karena laporan ditolak PIC (bukan penutupan koordinator). */
    public const STATUS_REPORT_REJECTED = 'Report Rejected';

    /**
     * @return list<string>
     */
    public static function allowedSubStatuses(): array
    {
        return [
            self::SUB_STATUS_TRIAGE,
            self::SUB_STATUS_ANALYSIS,
            self::SUB_STATUS_RESPONSE,
            self::SUB_STATUS_RESOLUTION,
        ];
    }

    public function isClosed(): bool
    {
        return $this->status === self::STATUS_CLOSED;
    }

    public function isReportRejected(): bool
    {
        return $this->report_status === self::REPORT_STATUS_REJECTED
            || $this->status === self::STATUS_REPORT_REJECTED;
    }

    /**
     * Tiket tidak lagi dapat ditugaskan / dilanjutkan alur penanganan.
     */
    public function isTerminal(): bool
    {
        return $this->isClosed() || $this->isReportRejected();
    }

    /**
     * Fase kerja responder untuk badge/filter (butuh withCount responseActions pada query daftar bisa).
     *
     * @return array{slug: string, label: string, badge_class: string}
     */
    public function responderWorkPhase(?int $responseActionsCount = null): array
    {
        if ($this->sub_status === self::SUB_STATUS_RESOLUTION) {
            return [
                'slug' => 'resolved',
                'label' => 'Selesai ditangani',
                'badge_class' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-300',
            ];
        }

        if ($this->sub_status === self::SUB_STATUS_RESPONSE) {
            $count = $responseActionsCount;
            if ($count === null && isset($this->response_actions_count)) {
                $count = (int) $this->response_actions_count;
            }
            if ($count === null) {
                $count = (int) $this->responseActions()->count();
            }

            if ($count < 1) {
                return [
                    'slug' => 'ready_for_response',
                    'label' => 'Siap ditangani',
                    'badge_class' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-300',
                ];
            }

            return [
                'slug' => 'in_progress',
                'label' => 'Ditangani',
                'badge_class' => 'bg-amber-100 text-amber-900 dark:bg-amber-900/40 dark:text-amber-200',
            ];
        }

        return [
            'slug' => 'other',
            'label' => $this->sub_status ?? '—',
            'badge_class' => 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300',
        ];
    }

    /**
     * PIC: Pending report → Verified, valid, and ticket becomes Open for assignment.
     */
    public function verifyReport(User $user): void
    {
        if ($this->report_status !== self::REPORT_STATUS_PENDING) {
            throw new Exception('Laporan tidak dalam status menunggu verifikasi.');
        }

        if ($this->status !== self::STATUS_AWAITING_VERIFICATION) {
            throw new Exception('Tiket tidak dalam antrean verifikasi PIC.');
        }

        $this->report_status = self::REPORT_STATUS_VERIFIED;
        $this->report_is_valid = true;
        $this->status = self::STATUS_OPEN;
        $this->save();

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'report_verified',
            'data' => json_encode([
                'report_status' => self::REPORT_STATUS_VERIFIED,
                'status' => self::STATUS_OPEN,
            ]),
        ]);
    }

    /**
     * PIC: menolak laporan (tidak valid / false report). Alasan wajib untuk audit.
     */
    public function rejectReport(User $user, string $reason): void
    {
        if ($this->report_status !== self::REPORT_STATUS_PENDING) {
            throw new Exception('Laporan tidak dalam status menunggu verifikasi.');
        }

        if ($this->status !== self::STATUS_AWAITING_VERIFICATION) {
            throw new Exception('Tiket tidak dalam antrean verifikasi PIC.');
        }

        $this->report_status = self::REPORT_STATUS_REJECTED;
        $this->report_is_valid = false;
        $this->report_rejection_reason = $reason;
        $this->status = self::STATUS_REPORT_REJECTED;
        $this->closed_at = now();
        $this->save();

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'report_rejected',
            'data' => json_encode([
                'report_status' => self::REPORT_STATUS_REJECTED,
                'status' => self::STATUS_REPORT_REJECTED,
                'reason' => $reason,
            ]),
        ]);
    }

    /**
     * Update sub_status while ticket is On Progress (handled by analis/responder).
     */
    public function updateSubStatus(string $newSubStatus, User $user, bool $isSystem = false): void
    {
        if ($this->status !== self::STATUS_ON_PROGRESS) {
            throw new Exception('Sub-status hanya dapat diubah saat tiket On Progress.');
        }

        if (! in_array($newSubStatus, self::allowedSubStatuses(), true)) {
            throw new Exception('Nilai sub_status tidak valid.');
        }

        if (! $isSystem) {
            $gate = Gate::forUser($user);
            if (! $gate->allows('ticket.update_status')) {
                throw new Exception('Anda tidak memiliki izin untuk memperbarui sub-status.');
            }

            if (! $gate->allows('ticket.analyze') && ! $gate->allows('ticket.respond')) {
                throw new Exception('Anda tidak memiliki izin analisis atau respons untuk memperbarui sub-status.');
            }

            $assigned = $this->assignments()
                ->where('user_id', $user->id)
                ->where('is_active', true)
                ->exists();

            if (! $assigned) {
                throw new Exception('Anda tidak ditugaskan pada tiket ini.');
            }
        }

        $oldSub = $this->sub_status;

        $this->sub_status = $newSubStatus;
        $this->save();

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'sub_status_updated',
            'data' => json_encode([
                'from' => $oldSub,
                'to' => $newSubStatus,
            ]),
        ]);
    }

    /**
     * Koordinator: kembalikan sub-status ke Response setelah Resolution agar responder dapat mencatat tindakan lagi.
     * Izin reopenResponseRecording harus sudah dicek pemanggil.
     */
    public function reopenResponsePhaseForAdditionalActions(User $coordinator): void
    {
        if ($this->status !== self::STATUS_ON_PROGRESS) {
            throw new Exception('Tiket harus On Progress.');
        }

        if ($this->sub_status !== self::SUB_STATUS_RESOLUTION) {
            throw new Exception('Tiket tidak dalam fase Resolution.');
        }

        $this->updateSubStatus(self::SUB_STATUS_RESPONSE, $coordinator, true);
    }

    /**
     * Koordinator menutup tiket yang telah selesai ditangani.
     */
    public function close(User $user, bool $isSystem = false): void
    {
        if ($this->status === self::STATUS_CLOSED) {
            throw new Exception('Tiket sudah ditutup.');
        }

        if (! $isSystem) {
            if (! $user->hasRole('koordinator')) {
                throw new Exception('Hanya koordinator yang dapat menutup tiket.');
            }
        }

        $this->status = self::STATUS_CLOSED;
        $this->closed_at = now();
        $this->save();

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $user->id,
            'action' => 'closed',
            'data' => json_encode([
                'status' => self::STATUS_CLOSED,
            ]),
        ]);
    }

    /**
     * @deprecated Gunakan updateSubStatus; tetap dipakai untuk kompatibilitas nama method.
     */
    public function updateStatus($newValue, User $user, bool $isSystem = false): void
    {
        if ($newValue === self::STATUS_CLOSED) {
            $this->close($user, $isSystem);

            return;
        }

        $mapped = self::mapLegacyStatusToSubStatus((string) $newValue);
        if ($mapped !== null) {
            $this->updateSubStatus($mapped, $user, $isSystem);

            return;
        }

        $this->updateSubStatus((string) $newValue, $user, $isSystem);
    }

    /**
     * Normalisasi nilai status lama (lowercase) ke sub_status baru.
     */
    public static function mapLegacyStatusToSubStatus(string $legacy): ?string
    {
        $map = [
            'triaged' => self::SUB_STATUS_TRIAGE,
            'analyzed' => self::SUB_STATUS_ANALYSIS,
            'responded' => self::SUB_STATUS_RESPONSE,
        ];

        return $map[strtolower($legacy)] ?? null;
    }

    public function assignments()
    {
        return $this->hasMany(TicketAssignment::class);
    }

    public function assignTo(int $userId, ?User $assignedBy): TicketAssignment
    {
        $this->assignments()
            ->where('is_active', true)
            ->where('kind', TicketAssignment::KIND_ASSIGNED_PRIMARY)
            ->update([
                'is_active' => false,
                'unassigned_at' => now(),
            ]);

        $assignment = TicketAssignment::create([
            'ticket_id' => $this->id,
            'user_id' => $userId,
            'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        $this->applyHandoffToAnalystAfterPrimaryAssign($userId);

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $assignedBy->id ?? null,
            'action' => 'assigned',
            'data' => json_encode([
                'assigned_to' => $userId,
                'kind' => TicketAssignment::KIND_ASSIGNED_PRIMARY,
            ]),
        ]);

        event(new TicketAssigned($this, $userId, $assignedBy));

        return $assignment;
    }

    public function addContributor(int $userId, ?User $assignedBy): TicketAssignment
    {
        $assignment = TicketAssignment::create([
            'ticket_id' => $this->id,
            'user_id' => $userId,
            'kind' => TicketAssignment::KIND_CONTRIBUTOR,
            'assigned_at' => now(),
            'is_active' => true,
        ]);

        TicketLog::create([
            'ticket_id' => $this->id,
            'user_id' => $assignedBy->id ?? null,
            'action' => 'contributor_assigned',
            'data' => json_encode([
                'assigned_to' => $userId,
                'kind' => TicketAssignment::KIND_CONTRIBUTOR,
            ]),
        ]);

        event(new TicketAssigned($this, $userId, $assignedBy));

        return $assignment;
    }

    private function applyHandoffToAnalystAfterPrimaryAssign(int $assigneeUserId): void
    {
        $assignee = User::query()->find($assigneeUserId);
        if ($assignee === null || (! $assignee->can('ticket.analyze') && ! $assignee->can('ticket.respond'))) {
            return;
        }

        if ($this->status !== self::STATUS_OPEN || $this->report_status !== self::REPORT_STATUS_VERIFIED) {
            return;
        }

        $this->status = self::STATUS_ON_PROGRESS;
        $this->sub_status = self::SUB_STATUS_TRIAGE;
        $this->save();
    }
}
