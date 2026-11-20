<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class RunBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup
                            {--type=full : Tipe backup yang dibuat (full atau db)}
                            {--prune : Terapkan rotasi retensi backup lama setelah backup berhasil}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Buat arsip backup sistem Ruang Temu Buku sesuai standar ISO/IEC 27001 A.8.13 & NIST CP-9';

    public function handle(BackupService $backupService): int
    {
        $type = strtolower((string) $this->option('type'));
        if (! in_array($type, ['full', 'db'], true)) {
            $this->error('Tipe backup tidak valid. Gunakan --type=full atau --type=db.');

            return self::FAILURE;
        }

        try {
            $result = $backupService->createBackup($type);

            $encLabel = $result['encrypted'] ? 'Terenkripsi AES-256' : 'Standar';
            ActivityLogger::record(
                action: 'backup.created',
                description: "Backup terjadwal/CLI berhasil dibuat: {$result['filename']} (Tipe: {$result['backup_type']}, {$encLabel}, {$result['total_records']} baris data, {$result['cover_files_count']} sampul).",
            );

            $this->info("Backup berhasil dibuat: {$result['filename']}");

            if ((bool) $this->option('prune')) {
                $pruned = $backupService->pruneOldBackups();
                if ($pruned !== []) {
                    ActivityLogger::record(
                        action: 'backup.pruned',
                        description: 'Rotasi retensi backup menghapus '.count($pruned).' arsip lama: '.implode(', ', $pruned),
                    );
                    $this->info('Retensi backup membersihkan '.count($pruned).' arsip lama.');
                }
            }

            return self::SUCCESS;
        } catch (Throwable $e) {
            $this->error('Gagal membuat backup: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
