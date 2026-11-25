<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    /**
     * Nilai bawaan status pembayaran saat inisiasi awal.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
    ];

    /**
     * Atribut yang diizinkan untuk mass assignment saat pembuatan order awal.
     * status, settled_at, transaction_id, payment_type, dan payment_details dikecualikan
     * agar hanya diperbarui secara otoritatif oleh verifikasi webhook Midtrans.
     *
     * @var list<string>
     */
    protected $fillable = [
        'order_id',
        'fine_id',
        'user_id',
        'gross_amount',
        'snap_token',
    ];

    protected function casts(): array
    {
        return [
            'gross_amount' => 'integer',
            'status' => PaymentStatus::class,
            'payment_details' => 'array',
            'settled_at' => 'datetime',
        ];
    }

    public function fine(): BelongsTo
    {
        return $this->belongsTo(Fine::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isPaid(): bool
    {
        return $this->status === PaymentStatus::Paid;
    }

    public function formattedAmount(): string
    {
        return 'Rp '.number_format((int) $this->gross_amount, 0, ',', '.');
    }
}
