<?php

namespace App\Enums;

enum RoleName: string
{
    case Admin = 'admin';
    case Petugas = 'petugas';
    case Anggota = 'anggota';

    public function label(): string
    {
        return match ($this) {
            self::Admin => 'Admin',
            self::Petugas => 'Petugas',
            self::Anggota => 'Anggota',
        };
    }
}
