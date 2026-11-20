<?php

namespace App\Models;

use App\Enums\FineStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Fine extends Model
{
    protected $fillable = [
        'loan_id',
        'member_id',
        'overdue_days',
        'fine_per_day',
        'total_amount',
        'status',
        'paid_at',
        'waived_by',
        'waived_reason',
    ];

    protected function casts(): array
    {
        return [
            'overdue_days' => 'integer',
            'fine_per_day' => 'integer',
            'total_amount' => 'integer',
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
        return in_array($this->status, [FineStatus::Unpaid, FineStatus::PendingPayment], true);
    }

    public function formattedAmount(): string
    {
        return 'Rp '.number_format($this->total_amount, 0, ',', '.');
    }
}
