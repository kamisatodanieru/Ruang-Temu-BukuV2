<?php

namespace App\Models;

use App\Enums\FineStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fine extends Model
{
    /**
     * Nilai bawaan model untuk menjamin konsistensi state awal sebelum disimpan.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'paid_amount' => 0,
        'status' => 'unpaid',
    ];

    /**
     * Atribut yang diizinkan untuk mass assignment saat pembuatan awal.
     * status, paid_amount, paid_at, waived_by, dan waived_reason dikecualikan
     * agar perubahan status finansial hanya terjadi secara eksplisit di service.
     *
     * @var list<string>
     */
    protected $fillable = [
        'loan_id',
        'member_id',
        'overdue_days',
        'fine_per_day',
        'total_amount',
    ];

    protected function casts(): array
    {
        return [
            'overdue_days' => 'integer',
            'fine_per_day' => 'integer',
            'total_amount' => 'integer',
            'paid_amount' => 'integer',
            'status' => FineStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    public function loan(): BelongsTo
    {
        return $this->belongsTo(Loan::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function waiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'waived_by');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function isPaid(): bool
    {
        return $this->status === FineStatus::Paid || $this->status === FineStatus::Waived;
    }

    public function isPayable(): bool
    {
        return in_array($this->status, [FineStatus::Unpaid, FineStatus::PendingPayment], true)
            && $this->remainingAmount() > 0;
    }

    public function remainingAmount(): int
    {
        if ($this->isPaid()) {
            return 0;
        }

        return max(0, (int) $this->total_amount - (int) $this->paid_amount);
    }

    public function unpaidDays(): int
    {
        $remaining = $this->remainingAmount();
        if ($remaining <= 0) {
            return 0;
        }

        $rate = max(1, (int) $this->fine_per_day);

        return max(1, (int) ceil($remaining / $rate));
    }

    public function formattedAmount(): string
    {
        return 'Rp '.number_format((int) $this->total_amount, 0, ',', '.');
    }

    public function formattedPaidAmount(): string
    {
        return 'Rp '.number_format((int) $this->paid_amount, 0, ',', '.');
    }

    public function formattedRemainingAmount(): string
    {
        return 'Rp '.number_format($this->remainingAmount(), 0, ',', '.');
    }
}
