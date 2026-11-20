<?php

namespace App\Enums;

enum LoanStatus: string
{
    case Pending = 'pending';
    case Borrowed = 'borrowed';
    case ReturnPending = 'return_pending';
    case Returned = 'returned';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Persetujuan',
            self::Borrowed => 'Sedang Dipinjam',
            self::ReturnPending => 'Menunggu Pengembalian',
            self::Returned => 'Dikembalikan',
            self::Rejected => 'Ditolak',
            self::Cancelled => 'Dibatalkan',
        };
    }
}
