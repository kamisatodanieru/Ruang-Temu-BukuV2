<?php

namespace App\Console\Commands;

use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Console\Command;
use Throwable;

class VerifyBackupCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:backup-verify
                            {filename? : Nama file backup yang akan diverifikasi (kosongkan untuk memverifikasi backup terbaru)}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifikasi integritas SHA-256 dan tanda tangan manifest arsip backup (NIST SP 800-53 CP-9(1))';

    public function handle(BackupService $backupService): int
    {
        $filename = $this->argument('filename');

        if (! is_string($filename) || trim($filename) === '') {
            $backups = $backupService->listBackups();
            if ($backups === []) {
                $this->warn('Belum ada arsip backup untuk diverifikasi.');

                return self::FAILURE;
            }
            $filename = $backups[0]['filename'];
        }

        try {
            $report = $backupService->verifyBackup($filename);

            if ($report['valid']) {
                ActivityLogger::record(
                    action: 'backup.verified',
                    description: "Verifikasi integritas SHA-256 berhasil pada arsip {$filename} ({$report['checked_files']} komponen tervalidasi).",
                );

                $this->info("Integritas arsip [{$filename}] VALID ({$report['checked_files']} file terverifikasi SHA-256).");

                return self::SUCCESS;
            }

            $errorSummary = implode(' | ', $report['errors']);
            ActivityLogger::record(
                action: 'backup.verify_failed',
                description: "Verifikasi integritas gagal pada arsip {$filename}: {$errorSummary}",
            );

            $this->error("Integritas arsip [{$filename}] TIDAK VALID: {$errorSummary}");

            return self::FAILURE;
        } catch (Throwable $e) {
            $this->error('Gagal memverifikasi backup: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
