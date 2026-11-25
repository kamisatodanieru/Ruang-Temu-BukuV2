<?php

namespace App\Models;

use App\Enums\FineStatus;
use App\Enums\LoanStatus;
use Database\Factories\LoanFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Loan extends Model
{
    /** @use HasFactory<LoanFactory> */
    use HasFactory;

    protected $fillable = [
        'member_id',
        'book_id',
        'requested_at',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'borrowed_at' => 'datetime',
            'due_at' => 'datetime',
            'return_requested_at' => 'datetime',
            'returned_at' => 'datetime',
            'rejected_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'status' => LoanStatus::class,
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(User::class, 'member_id');
    }

    public function book(): BelongsTo
    {
        return $this->belongsTo(Book::class)->withTrashed();
    }

    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    public function rejector(): BelongsTo
    {
        return $this->belongsTo(User::class, 'rejected_by');
    }

    public function returnConfirmer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'returned_confirmed_by');
    }

    public function fine(): HasOne
    {
        return $this->hasOne(Fine::class);
    }

    /**
     * Transaksi dianggap overdue jika status = borrowed/return_pending, returned_at IS NULL, dan due_at < waktu sekarang.
     */
    public function isOverdue(): bool
    {
        return in_array($this->status, [LoanStatus::Borrowed, LoanStatus::ReturnPending], true)
            && $this->returned_at === null
            && $this->due_at !== null
            && $this->due_at->isPast();
    }

    public function overdueDays(): int
    {
        if (! $this->isOverdue()) {
            return 0;
        }

        return (int) max(1, ceil($this->due_at->diffInHours(now()) / 24));
    }

    public function effectiveOverdueDays(): int
    {
        $fine = $this->relationLoaded('fine') ? $this->fine : null;
        $storedDays = (int) ($fine?->overdue_days ?? 0);

        if ($this->isOverdue()) {
            return max($storedDays, $this->overdueDays());
        }

        return $storedDays;
    }

    public function effectiveFineTotal(): int
    {
        $fine = $this->relationLoaded('fine') ? $this->fine : null;
        $finePerDay = (int) ($fine?->fine_per_day ?: config('library.fine_per_day', 1000));
        $calculated = $this->isOverdue() ? ($this->overdueDays() * $finePerDay) : 0;

        return max((int) ($fine?->total_amount ?? 0), $calculated);
    }

    public function effectiveRemainingFine(): int
    {
        $fine = $this->relationLoaded('fine') ? $this->fine : null;

        if ($fine !== null && $fine->status === FineStatus::Waived) {
            return 0;
        }

        $paidAmount = (int) ($fine?->paid_amount ?? 0);
        if ($fine !== null && $fine->status === FineStatus::Paid && $paidAmount === 0) {
            $paidAmount = (int) $fine->total_amount;
        }

        return max(0, $this->effectiveFineTotal() - $paidAmount);
    }

    public function estimatedFineAmount(): int
    {
        return $this->effectiveFineTotal();
    }
}
