<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

// Jadwal Otomatis Backup & Verifikasi Integritas (ISO/IEC 27001 A.8.13, NIST CP-9 & CIS Control 11.2)
Schedule::command('app:backup --type=db --prune')
    ->dailyAt('02:00')
    ->withoutOverlapping()
    ->description('Backup database harian otomatis dengan rotasi retensi');

Schedule::command('app:backup-verify')
    ->dailyAt('02:30')
    ->withoutOverlapping()
    ->description('Verifikasi integritas SHA-256 harian atas backup terbaru (NIST CP-9(1))');

Schedule::command('app:backup --type=full --prune')
    ->weeklyOn(0, '03:00')
    ->withoutOverlapping()
    ->description('Full backup mingguan (Database + Sampul Buku)');
