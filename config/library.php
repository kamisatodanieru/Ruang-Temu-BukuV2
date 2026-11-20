<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Kebijakan Operasional Perpustakaan (Ruang Temu Buku)
    |--------------------------------------------------------------------------
    |
    | Nilai konfigurasi terpusat sesuai PRD v2.0 Bagian 5.1 & 15.
    |
    */

    'max_active_loans' => (int) env('LIBRARY_MAX_ACTIVE_LOANS', 3),

    'loan_duration_days' => (int) env('LIBRARY_LOAN_DURATION_DAYS', 7),

    'fine_per_day' => (int) env('LIBRARY_FINE_PER_DAY', 1000),

    /*
    |--------------------------------------------------------------------------
    | Kebijakan Backup & Pemulihan Sistem (ISO/IEC 27001 A.8.13 & NIST CP-9)
    |--------------------------------------------------------------------------
    |
    | Mengatur RPO, RTO, retensi arsip, disk penyimpanan, enkripsi AES-256
    | sesuai NIST SP 800-53 Rev. 5 CP-9(1) & CP-9(8), NIST CSF 2.0 PR.DS-11,
    | serta CIS Critical Security Controls v8 Control 11.
    |
    */
    'backup' => [
        'disk' => env('BACKUP_DISK', 'local'),
        'directory' => env('BACKUP_DIRECTORY', 'backups'),
        'rpo_hours' => (int) env('BACKUP_RPO_HOURS', 24),
        'rto_hours' => (int) env('BACKUP_RTO_HOURS', 4),
        'retention_days' => (int) env('BACKUP_RETENTION_DAYS', 30),
        'max_backups' => (int) env('BACKUP_MAX_FILES', 10),
        'archive_password' => env('BACKUP_ARCHIVE_PASSWORD'),
        'core_tables' => [
            'migrations',
            'roles',
            'users',
            'categories',
            'books',
            'tags',
            'book_tag',
            'loans',
            'fines',
            'payments',
            'activity_logs',
        ],
        'structure_only_tables' => [
            'password_reset_tokens',
            'sessions',
            'cache',
            'cache_locks',
            'jobs',
            'job_batches',
            'failed_jobs',
        ],
    ],
];
