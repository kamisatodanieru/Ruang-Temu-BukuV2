<?php

namespace App\Enums;

enum FineStatus: string
{
    case Unpaid = 'unpaid';
    case PendingPayment = 'pending_payment';
    case Paid = 'paid';
    case Waived = 'waived';

    public function label(): string
    {
        return match ($this) {
            self::Unpaid => 'Belum Dibayar',
            self::PendingPayment => 'Menunggu Pembayaran',
            self::Paid => 'Lunas',
            self::Waived => 'Dibebaskan',
        };
    }
}
