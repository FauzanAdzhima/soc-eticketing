<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Illuminate\Database\Eloquent\Relations\Pivot;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasApiTokens, HasFactory, Notifiable, HasRoles;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'public_id',
        'name',
        'email',
        'password',
        'organization_id',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }

    /**
     * Analis murni: punya antrean penugasan saja di navigasi (tanpa menu Daftar Tiket utama PIC/koordinator).
     */
    public function seesOnlyAnalystTicketListInNavigation(): bool
    {
        return $this->can('ticket.analyze')
            && ! $this->can('ticket.respond')
            && ! $this->hasRole('pic')
            && ! $this->can('ticket.view_all');
    }

    /**
     * Responder murni: antrean penanganan saja di navigasi (tanpa Daftar Tiket PIC/koordinator polos).
     */
    public function seesOnlyResponderTicketListInNavigation(): bool
    {
        return $this->can('ticket.respond')
            && ! $this->can('ticket.analyze')
            && ! $this->hasRole('pic')
            && ! $this->can('ticket.view_all');
    }

    /**
     * Di tabel "Daftar Tiket" (bukan scope analis), sembunyikan tombol Analisis bila user juga punya alur analis/responder
     * lewat menu "Analisis Tiket" (menghindari duplikasi untuk kombinasi PIC + analis/responder).
     */
    public function shouldHideAnalysisShortcutOnMainTicketList(): bool
    {
        return $this->hasRole('pic') && $this->can('ticket.analyze');
    }
}
