<?php

namespace App\Models;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use App\Enums\RoleName;
use App\Enums\UserStatus;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, Notifiable;

    /**
     * Atribut yang diizinkan untuk mass assignment.
     * role_id dan status sengaja dikecualikan untuk mencegah privilege escalation.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
    ];

    /**
     * Atribut yang disembunyikan saat serialisasi.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Definisi tipe cast atribut.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'password' => 'hashed',
            'status' => UserStatus::class,
        ];
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(Role::class);
    }

    public function activityLogs(): HasMany
    {
        return $this->hasMany(ActivityLog::class);
    }

    public function loans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id');
    }

    public function activeLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id')
            ->whereIn('status', [
                LoanStatus::Pending->value,
                LoanStatus::Borrowed->value,
                LoanStatus::ReturnPending->value,
            ]);
    }

    public function overdueLoans(): HasMany
    {
        return $this->hasMany(Loan::class, 'member_id')
            ->whereIn('status', [LoanStatus::Borrowed->value, LoanStatus::ReturnPending->value])
            ->whereNull('returned_at')
            ->where('due_at', '<', now());
    }

    public function fines(): HasMany
    {
        return $this->hasMany(Fine::class, 'member_id');
    }

    public function unpaidFines(): HasMany
    {
        return $this->hasMany(Fine::class, 'member_id')
            ->whereIn('status', [FineStatus::Unpaid->value, FineStatus::PendingPayment->value]);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function isActive(): bool
    {
        return $this->status === UserStatus::Active;
    }

    public function hasRole(RoleName|string ...$roles): bool
    {
        $currentRole = $this->role?->name;

        if ($currentRole === null) {
            return false;
        }

        foreach ($roles as $role) {
            $expected = $role instanceof RoleName ? $role->value : $role;
            if ($currentRole === $expected) {
                return true;
            }
        }

        return false;
    }

    public function isAdmin(): bool
    {
        return $this->hasRole(RoleName::Admin);
    }

    public function isPetugas(): bool
    {
        return $this->hasRole(RoleName::Petugas);
    }

    public function isAnggota(): bool
    {
        return $this->hasRole(RoleName::Anggota);
    }
}
