<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use ZipArchive;

class BackupService
{
    private const SQL_HEADER_MARKER = '-- RUANG TEMU BUKU SQL BACKUP DUMP';

    private const SQL_FOOTER_MARKER = '-- END OF RUANG TEMU BUKU BACKUP DUMP';

    /**
     * Validasi ketat nama file backup untuk mencegah Path Traversal.
     */
    public function isValidFilename(string $filename): bool
    {
        if (basename($filename) !== $filename) {
            return false;
        }

        return preg_match('/^backup-rtb-\d{8}-\d{6}-[a-z0-9]{6}-(full|db)\.zip$/', $filename) === 1;
    }

    /**
     * Buat arsip backup baru (.zip) sesuai standar ISO/IEC 27001 A.8.13 & NIST SP 800-53 CP-9.
     *
     * @return array{
     *     filename: string,
     *     path: string,
     *     size_bytes: int,
     *     backup_type: string,
     *     encrypted: bool,
     *     total_records: int,
     *     cover_files_count: int,
     *     manifest: array<string, mixed>
     * }
     */
    public function createBackup(string $type = 'full', ?User $actor = null): array
    {
        $type = strtolower(trim($type));
        if (! in_array($type, ['full', 'db'], true)) {
            throw new InvalidArgumentException('Tipe backup tidak valid. Gunakan "full" atau "db".');
        }

        $timestamp = now()->format('Ymd-His');
        $uniqueSuffix = strtolower(Str::random(6));
        $filename = "backup-rtb-{$timestamp}-{$uniqueSuffix}-{$type}.zip";

        $dump = $this->generateDatabaseDump();
        $sqlContent = $dump['sql'];

        $filesChecksum = [
            'database.sql' => hash('sha256', $sqlContent),
        ];

        /** @var array<string, string> $coverPayloads */
        $coverPayloads = [];

        if ($type === 'full' && Storage::disk('public')->exists('covers')) {
            $coverFiles = Storage::disk('public')->files('covers');
            foreach ($coverFiles as $coverFile) {
                $safeBase = basename($coverFile);
                if ($safeBase === '' || str_starts_with($safeBase, '.')) {
                    continue;
                }

                $content = Storage::disk('public')->get($coverFile);
                if ($content === null) {
                    continue;
                }

                $entryName = 'covers/'.$safeBase;
                $coverPayloads[$entryName] = $content;
                $filesChecksum[$entryName] = hash('sha256', $content);
            }
        }

        $password = $this->archivePassword();
        $isEncrypted = $password !== '';

        $manifestPayload = [
            'app_name' => (string) config('app.name', 'Ruang Temu Buku'),
            'backup_version' => '1.0',
            'filename' => $filename,
            'created_at' => now()->toIso8601String(),
            'created_by' => $actor?->email ?? 'system-scheduler',
            'backup_type' => $type,
            'standards_compliance' => [
                'ISO/IEC 27001:2022 A.8.13',
                'NIST SP 800-53 Rev. 5 CP-9, CP-9(1), CP-9(8)',
                'NIST CSF 2.0 PR.DS-11',
                'CIS Controls v8 Control 11',
            ],
            'policy' => [
                'rpo_hours' => (int) config('library.backup.rpo_hours', 24),
                'rto_hours' => (int) config('library.backup.rto_hours', 4),
                'retention_days' => (int) config('library.backup.retention_days', 30),
                'max_backups' => (int) config('library.backup.max_backups', 10),
            ],
            'encrypted' => $isEncrypted,
            'encryption_algorithm' => $isEncrypted ? 'AES-256' : 'none',
            'database_driver' => $dump['driver'],
            'table_counts' => $dump['table_counts'],
            'total_records' => $dump['total_records'],
            'cover_files_count' => count($coverPayloads),
            'files_checksum' => $filesChecksum,
        ];

        $manifestPayload['manifest_signature'] = $this->signManifest($manifestPayload);

        $tempZipPath = $this->createTempFilePath('rtb_backup_');

        try {
            $zip = new ZipArchive;
            $openResult = $zip->open($tempZipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE);
            if ($openResult !== true) {
                throw new RuntimeException('Gagal menginisialisasi arsip ZIP untuk backup.');
            }

            if ($isEncrypted) {
                $zip->setPassword($password);
            }

            $manifestJson = (string) json_encode($manifestPayload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
            $zip->addFromString('manifest.json', $manifestJson);
            if ($isEncrypted) {
                $zip->setEncryptionName('manifest.json', ZipArchive::EM_AES_256);
            }

            $zip->addFromString('database.sql', $sqlContent);
            if ($isEncrypted) {
                $zip->setEncryptionName('database.sql', ZipArchive::EM_AES_256);
            }

            foreach ($coverPayloads as $entryName => $coverBytes) {
                $zip->addFromString($entryName, $coverBytes);
                if ($isEncrypted) {
                    $zip->setEncryptionName($entryName, ZipArchive::EM_AES_256);
                }
            }

            $zip->close();

            $zipBytes = file_get_contents($tempZipPath);
            if ($zipBytes === false) {
                throw new RuntimeException('Gagal membaca arsip backup sementara.');
            }

            $storagePath = $this->backupDirectory().'/'.$filename;
            Storage::disk($this->backupDisk())->put($storagePath, $zipBytes);

            return [
                'filename' => $filename,
                'path' => $storagePath,
                'size_bytes' => strlen($zipBytes),
                'backup_type' => $type,
                'encrypted' => $isEncrypted,
                'total_records' => $dump['total_records'],
                'cover_files_count' => count($coverPayloads),
                'manifest' => $manifestPayload,
            ];
        } finally {
            if (is_file($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    /**
     * Verifikasi keandalan arsip dan integritas SHA-256 sesuai NIST SP 800-53 CP-9(1) & CIS 11.5.
     *
     * @return array{
     *     valid: bool,
     *     filename: string,
     *     checked_files: int,
     *     encrypted: bool,
     *     errors: list<string>,
     *     manifest: array<string, mixed>|null
     * }
     */
    public function verifyBackup(string $filename): array
    {
        if (! $this->isValidFilename($filename)) {
            throw new InvalidArgumentException('Format nama file backup tidak valid.');
        }

        $disk = Storage::disk($this->backupDisk());
        $storagePath = $this->backupDirectory().'/'.$filename;

        if (! $disk->exists($storagePath)) {
            return [
                'valid' => false,
                'filename' => $filename,
                'checked_files' => 0,
                'encrypted' => false,
                'errors' => ['File backup tidak ditemukan pada media penyimpanan.'],
                'manifest' => null,
            ];
        }

        $tempZipPath = $this->createTempFilePath('rtb_verify_');
        $errors = [];
        $checkedFiles = 0;
        $isEncrypted = false;
        $manifest = null;

        try {
            $rawZip = $disk->get($storagePath);
            if ($rawZip === null || $rawZip === '') {
                return [
                    'valid' => false,
                    'filename' => $filename,
                    'checked_files' => 0,
                    'encrypted' => false,
                    'errors' => ['File backup kosong atau tidak dapat dibaca.'],
                    'manifest' => null,
                ];
            }

            file_put_contents($tempZipPath, $rawZip);

            $zip = new ZipArchive;
            if ($zip->open($tempZipPath) !== true) {
                return [
                    'valid' => false,
                    'filename' => $filename,
                    'checked_files' => 0,
                    'encrypted' => false,
                    'errors' => ['Arsip ZIP rusak dan tidak dapat dibuka.'],
                    'manifest' => null,
                ];
            }

            $manifestStat = $zip->statName('manifest.json');
            $manifestEncrypted = is_array($manifestStat)
                && (($manifestStat['encryption_method'] ?? 0) === ZipArchive::EM_AES_256);

            if ($manifestEncrypted) {
                $isEncrypted = true;
                $password = $this->archivePassword();
                if ($password === '') {
                    $zip->close();

                    return [
                        'valid' => false,
                        'filename' => $filename,
                        'checked_files' => 0,
                        'encrypted' => true,
                        'errors' => ['Kata sandi enkripsi arsip belum diatur.'],
                        'manifest' => null,
                    ];
                }
                $zip->setPassword($password);
            }

            $manifestRaw = @$zip->getFromName('manifest.json');
            if ($manifestRaw === false) {
                $zip->close();

                return [
                    'valid' => false,
                    'filename' => $filename,
                    'checked_files' => 0,
                    'encrypted' => $manifestEncrypted,
                    'errors' => [$manifestEncrypted ? 'Kata sandi enkripsi arsip tidak cocok atau manifest rusak.' : 'Data informasi arsip tidak ditemukan.'],
                    'manifest' => null,
                ];
            }

            $decoded = json_decode($manifestRaw, true);
            if (! is_array($decoded)) {
                $zip->close();

                return [
                    'valid' => false,
                    'filename' => $filename,
                    'checked_files' => 0,
                    'encrypted' => false,
                    'errors' => ['Data informasi arsip tidak valid atau rusak.'],
                    'manifest' => null,
                ];
            }

            $manifest = $decoded;
            $isEncrypted = (bool) ($manifest['encrypted'] ?? false);

            // 1. Verifikasi tanda tangan manifest
            $providedSignature = (string) ($manifest['manifest_signature'] ?? '');
            $manifestForVerify = $manifest;
            unset($manifestForVerify['manifest_signature']);
            $expectedSignature = $this->signManifest($manifestForVerify);

            if ($providedSignature === '' || ! hash_equals($expectedSignature, $providedSignature)) {
                $errors[] = 'Tanda tangan arsip tidak valid atau telah diubah.';
            }

            // 2. Konfigurasi kunci dekripsi bila arsip terenkripsi
            if ($isEncrypted) {
                $password = $this->archivePassword();
                if ($password === '') {
                    $errors[] = 'Kata sandi enkripsi arsip belum diatur.';
                } else {
                    $zip->setPassword($password);
                }

                $dbStat = $zip->statName('database.sql');
                if (is_array($dbStat) && ($dbStat['encryption_method'] ?? 0) !== ZipArchive::EM_AES_256) {
                    $errors[] = 'Enkripsi berkas basis data tidak sesuai.';
                }
            }

            // 3. Verifikasi keutuhan tiap berkas di dalam arsip
            $checksums = $manifest['files_checksum'] ?? [];
            if (! is_array($checksums) || $checksums === [] || ! isset($checksums['database.sql'])) {
                $errors[] = 'Daftar pemeriksaan berkas pada arsip tidak lengkap.';
            } else {
                foreach ($checksums as $entryName => $expectedHash) {
                    $entryContent = @$zip->getFromName((string) $entryName);
                    if ($entryContent === false) {
                        $errors[] = "Berkas {$entryName} gagal dibaca atau kata sandi tidak cocok.";

                        continue;
                    }

                    $actualHash = hash('sha256', $entryContent);
                    if (! hash_equals((string) $expectedHash, $actualHash)) {
                        $errors[] = "Berkas {$entryName} rusak atau telah diubah.";

                        continue;
                    }

                    if ($entryName === 'database.sql') {
                        if (! str_starts_with($entryContent, self::SQL_HEADER_MARKER)
                            || ! str_contains($entryContent, self::SQL_FOOTER_MARKER)) {
                            $errors[] = 'Isi berkas basis data tidak utuh.';
                        }
                    }

                    $checkedFiles++;
                }
            }

            $zip->close();
        } finally {
            if (is_file($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }

        return [
            'valid' => $errors === [],
            'filename' => $filename,
            'checked_files' => $checkedFiles,
            'encrypted' => $isEncrypted,
            'errors' => $errors,
            'manifest' => $manifest,
        ];
    }

    /**
     * Daftar seluruh file backup yang tersimpan beserta metadata manifest-nya.
     *
     * @return list<array{
     *     filename: string,
     *     size_bytes: int,
     *     size_formatted: string,
     *     created_at: Carbon,
     *     backup_type: string,
     *     encrypted: bool,
     *     encryption_algorithm: string,
     *     total_records: int,
     *     cover_files_count: int,
     *     created_by: string
     * }>
     */
    public function listBackups(): array
    {
        $disk = Storage::disk($this->backupDisk());
        $dir = $this->backupDirectory();

        if (! $disk->exists($dir)) {
            return [];
        }

        $files = $disk->files($dir);
        $items = [];

        foreach ($files as $filePath) {
            $filename = basename($filePath);
            if (! $this->isValidFilename($filename)) {
                continue;
            }

            $sizeBytes = (int) $disk->size($filePath);
            $lastModified = Carbon::createFromTimestamp($disk->lastModified($filePath));
            $manifest = $this->readManifestFromStorage($filePath);

            $typeFromFilename = str_ends_with($filename, '-full.zip') ? 'full' : 'db';
            $createdAt = isset($manifest['created_at'])
                ? Carbon::parse((string) $manifest['created_at'])
                : $lastModified;

            $items[] = [
                'filename' => $filename,
                'size_bytes' => $sizeBytes,
                'size_formatted' => $this->formatBytes($sizeBytes),
                'created_at' => $createdAt,
                'backup_type' => (string) ($manifest['backup_type'] ?? $typeFromFilename),
                'encrypted' => (bool) ($manifest['encrypted'] ?? false),
                'encryption_algorithm' => (string) ($manifest['encryption_algorithm'] ?? 'none'),
                'total_records' => (int) ($manifest['total_records'] ?? 0),
                'cover_files_count' => (int) ($manifest['cover_files_count'] ?? 0),
                'created_by' => (string) ($manifest['created_by'] ?? 'system'),
            ];
        }

        usort($items, fn (array $a, array $b): int => strcmp($b['filename'], $a['filename']));

        return $items;
    }

    /**
     * Terapkan kebijakan retensi sesuai ISO/IEC 27001 A.8.13 & CIS Control 11.1.
     * Menghapus backup yang melebihi batas jumlah (max_backups) atau usia (retention_days).
     *
     * @return list<string> Daftar nama file yang dihapus
     */
    public function pruneOldBackups(): array
    {
        $backups = $this->listBackups();
        $maxBackups = max(1, (int) config('library.backup.max_backups', 10));
        $retentionDays = max(1, (int) config('library.backup.retention_days', 30));
        $cutoffDate = now()->subDays($retentionDays);

        $pruned = [];

        foreach ($backups as $index => $backup) {
            $exceedsCount = $index >= $maxBackups;
            $exceedsAge = $backup['created_at']->lt($cutoffDate) && $index >= 1;

            if ($exceedsCount || $exceedsAge) {
                if ($this->deleteBackup($backup['filename'])) {
                    $pruned[] = $backup['filename'];
                }
            }
        }

        return $pruned;
    }

    /**
     * Hapus satu file backup yang valid.
     */
    public function deleteBackup(string $filename): bool
    {
        if (! $this->isValidFilename($filename)) {
            throw new InvalidArgumentException('Format nama file backup tidak valid.');
        }

        $disk = Storage::disk($this->backupDisk());
        $path = $this->backupDirectory().'/'.$filename;

        if (! $disk->exists($path)) {
            return false;
        }

        return $disk->delete($path);
    }

    public function backupExists(string $filename): bool
    {
        if (! $this->isValidFilename($filename)) {
            return false;
        }

        return Storage::disk($this->backupDisk())->exists($this->backupDirectory().'/'.$filename);
    }

    public function backupDisk(): string
    {
        return (string) config('library.backup.disk', 'local');
    }

    public function backupDirectory(): string
    {
        return trim((string) config('library.backup.directory', 'backups'), '/');
    }

    public function isEncryptionConfigured(): bool
    {
        return $this->archivePassword() !== '';
    }

    /**
     * Bangun SQL Dump konsisten untuk seluruh tabel utama dan struktur tabel ephemeral.
     *
     * @return array{
     *     sql: string,
     *     table_counts: array<string, int>,
     *     total_records: int,
     *     driver: string
     * }
     */
    private function generateDatabaseDump(): array
    {
        $connection = DB::connection();
        $driver = $connection->getDriverName();
        $pdo = $connection->getPdo();

        /** @var list<string> $coreTables */
        $coreTables = (array) config('library.backup.core_tables', []);
        /** @var list<string> $structureOnlyTables */
        $structureOnlyTables = (array) config('library.backup.structure_only_tables', []);

        $lines = [
            self::SQL_HEADER_MARKER,
            '-- Application: '.config('app.name', 'Ruang Temu Buku'),
            '-- Generated At: '.now()->toIso8601String(),
            '-- Database Driver: '.$driver,
            '-- Standards: ISO/IEC 27001:2022 A.8.13, NIST SP 800-53 Rev. 5 CP-9',
            '',
        ];

        $isMysql = in_array($driver, ['mysql', 'mariadb'], true);
        if ($isMysql) {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=0;';
            $lines[] = 'SET SQL_MODE="NO_AUTO_VALUE_ON_ZERO";';
            $lines[] = '';
            $connection->unprepared('SET SESSION TRANSACTION ISOLATION LEVEL REPEATABLE READ');
            $connection->unprepared('START TRANSACTION WITH CONSISTENT SNAPSHOT');
        } else {
            $lines[] = 'PRAGMA foreign_keys=OFF;';
            $lines[] = '';
        }

        $tableCounts = [];
        $totalRecords = 0;

        try {
            foreach ($coreTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $lines[] = '-- --------------------------------------------------------';
                $lines[] = "-- Struktur & Data Tabel: `{$table}`";
                $lines[] = '-- --------------------------------------------------------';
                $lines[] = "DROP TABLE IF EXISTS `{$table}`;";

                $createSql = $this->getCreateTableSql($driver, $table);
                if ($createSql !== '') {
                    $lines[] = $createSql.';';
                }

                $rowCount = 0;
                $rows = DB::table($table)->get();

                foreach ($rows as $row) {
                    $rowArray = (array) $row;
                    $columns = array_map(fn (string $col): string => "`{$col}`", array_keys($rowArray));
                    $values = array_map(fn (mixed $val): string => $this->quoteSqlValue($pdo, $val), array_values($rowArray));

                    $lines[] = sprintf(
                        'INSERT INTO `%s` (%s) VALUES (%s);',
                        $table,
                        implode(', ', $columns),
                        implode(', ', $values)
                    );
                    $rowCount++;
                }

                $lines[] = '';
                $tableCounts[$table] = $rowCount;
                $totalRecords += $rowCount;
            }

            foreach ($structureOnlyTables as $table) {
                if (! Schema::hasTable($table)) {
                    continue;
                }

                $lines[] = '-- --------------------------------------------------------';
                $lines[] = "-- Struktur Tabel Ephemeral (Tanpa Data Sensitif/Sesi): `{$table}`";
                $lines[] = '-- --------------------------------------------------------';
                $lines[] = "DROP TABLE IF EXISTS `{$table}`;";

                $createSql = $this->getCreateTableSql($driver, $table);
                if ($createSql !== '') {
                    $lines[] = $createSql.';';
                }

                $lines[] = '';
            }
        } finally {
            if ($isMysql) {
                $connection->unprepared('COMMIT');
            }
        }

        if ($isMysql) {
            $lines[] = 'SET FOREIGN_KEY_CHECKS=1;';
        } else {
            $lines[] = 'PRAGMA foreign_keys=ON;';
        }

        $lines[] = self::SQL_FOOTER_MARKER;
        $lines[] = '';

        return [
            'sql' => implode("\n", $lines),
            'table_counts' => $tableCounts,
            'total_records' => $totalRecords,
            'driver' => $driver,
        ];
    }

    private function getCreateTableSql(string $driver, string $table): string
    {
        if (in_array($driver, ['mysql', 'mariadb'], true)) {
            $result = DB::select("SHOW CREATE TABLE `{$table}`");
            if (isset($result[0])) {
                $row = (array) $result[0];

                return (string) ($row['Create Table'] ?? '');
            }

            return '';
        }

        if ($driver === 'sqlite') {
            $result = DB::select(
                "SELECT sql FROM sqlite_master WHERE type = 'table' AND name = ?",
                [$table]
            );

            if (isset($result[0])) {
                $row = (array) $result[0];

                return (string) ($row['sql'] ?? '');
            }
        }

        return '';
    }

    private function quoteSqlValue(\PDO $pdo, mixed $value): string
    {
        if ($value === null) {
            return 'NULL';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_int($value) || is_float($value)) {
            return (string) $value;
        }

        return $pdo->quote((string) $value);
    }

    /**
     * @param  array<string, mixed>  $manifestPayload
     */
    private function signManifest(array $manifestPayload): string
    {
        ksort($manifestPayload);
        $canonical = (string) json_encode($manifestPayload, JSON_UNESCAPED_SLASHES);
        $secret = (string) config('app.key', 'ruang-temu-buku-backup-key');

        return hash_hmac('sha256', $canonical, $secret);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function readManifestFromStorage(string $storagePath): ?array
    {
        $disk = Storage::disk($this->backupDisk());
        $tempZipPath = $this->createTempFilePath('rtb_meta_');

        try {
            $rawZip = $disk->get($storagePath);
            if ($rawZip === null || $rawZip === '') {
                return null;
            }

            file_put_contents($tempZipPath, $rawZip);

            $zip = new ZipArchive;
            if ($zip->open($tempZipPath) !== true) {
                return null;
            }

            $password = $this->archivePassword();
            if ($password !== '') {
                $zip->setPassword($password);
            }

            $manifestRaw = @$zip->getFromName('manifest.json');
            $zip->close();

            if ($manifestRaw === false) {
                return null;
            }

            $decoded = json_decode($manifestRaw, true);

            return is_array($decoded) ? $decoded : null;
        } finally {
            if (is_file($tempZipPath)) {
                @unlink($tempZipPath);
            }
        }
    }

    private function archivePassword(): string
    {
        return trim((string) config('library.backup.archive_password', ''));
    }

    private function createTempFilePath(string $prefix): string
    {
        $privateTempDir = storage_path('app/private/temp');
        if (! is_dir($privateTempDir)) {
            @mkdir($privateTempDir, 0700, true);
        }

        $targetDir = is_dir($privateTempDir) && is_writable($privateTempDir)
            ? $privateTempDir
            : sys_get_temp_dir();

        $tempFile = tempnam($targetDir, $prefix);
        if ($tempFile === false) {
            throw new RuntimeException('Tidak dapat membuat file sementara di direktori sistem.');
        }

        return $tempFile;
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes < 1024) {
            return $bytes.' B';
        }

        $kb = $bytes / 1024;
        if ($kb < 1024) {
            return number_format($kb, 2).' KB';
        }

        $mb = $kb / 1024;

        return number_format($mb, 2).' MB';
    }
}
