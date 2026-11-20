<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ActivityLogger;
use App\Services\BackupService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Throwable;

class BackupController extends Controller
{
    public function __construct(
        private readonly BackupService $backupService,
    ) {}

    public function index(Request $request): View
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $backups = $this->backupService->listBackups();

        $policy = [
            'disk' => $this->backupService->backupDisk(),
            'rpo_hours' => (int) config('library.backup.rpo_hours', 24),
            'rto_hours' => (int) config('library.backup.rto_hours', 4),
            'retention_days' => (int) config('library.backup.retention_days', 30),
            'max_backups' => (int) config('library.backup.max_backups', 10),
            'encryption_enabled' => $this->backupService->isEncryptionConfigured(),
        ];

        return view('admin.backups.index', [
            'backups' => $backups,
            'policy' => $policy,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $validated = $request->validate([
            'type' => ['required', 'string', 'in:full,db'],
        ], [
            'type.required' => 'Jenis cadangan wajib dipilih.',
            'type.in' => 'Jenis cadangan harus berupa Lengkap atau Basis Data.',
        ]);

        try {
            $result = $this->backupService->createBackup($validated['type'], $request->user());
            $pruned = $this->backupService->pruneOldBackups();

            $typeLabel = $result['backup_type'] === 'full' ? 'Lengkap' : 'Basis Data';
            $encLabel = $result['encrypted'] ? 'Terenkripsi' : 'Standar';
            ActivityLogger::record(
                action: 'backup.created',
                description: "Membuat cadangan {$result['filename']} ({$typeLabel}, {$encLabel}).",
                subject: null,
                actor: $request->user(),
                request: $request,
            );

            if ($pruned !== []) {
                ActivityLogger::record(
                    action: 'backup.pruned',
                    description: 'Menghapus '.count($pruned).' arsip cadangan lama secara otomatis.',
                    subject: null,
                    actor: $request->user(),
                    request: $request,
                );
            }

            return redirect()
                ->route('admin.backups.index')
                ->with('success', "Arsip cadangan {$result['filename']} berhasil dibuat.");
        } catch (Throwable) {
            return redirect()
                ->route('admin.backups.index')
                ->with('error', 'Gagal membuat arsip cadangan. Periksa ruang penyimpanan sistem.');
        }
    }

    public function verify(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $this->backupService->isValidFilename($filename) || ! $this->backupService->backupExists($filename)) {
            abort(404);
        }

        $report = $this->backupService->verifyBackup($filename);

        if ($report['valid']) {
            ActivityLogger::record(
                action: 'backup.verified',
                description: "Memeriksa keutuhan arsip {$filename}: valid ({$report['checked_files']} berkas).",
                subject: null,
                actor: $request->user(),
                request: $request,
            );

            return redirect()
                ->route('admin.backups.index')
                ->with('success', "Arsip {$filename} utuh dan siap digunakan.");
        }

        $errorSummary = implode(' ', $report['errors']);
        ActivityLogger::record(
            action: 'backup.verify_failed',
            description: "Pemeriksaan arsip {$filename} gagal: {$errorSummary}",
            subject: null,
            actor: $request->user(),
            request: $request,
        );

        return redirect()
            ->route('admin.backups.index')
            ->with('error', "Pemeriksaan arsip {$filename} gagal: {$errorSummary}");
    }

    public function download(Request $request, string $filename): StreamedResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $this->backupService->isValidFilename($filename) || ! $this->backupService->backupExists($filename)) {
            abort(404);
        }

        ActivityLogger::record(
            action: 'backup.downloaded',
            description: "Mengunduh arsip cadangan: {$filename}",
            subject: null,
            actor: $request->user(),
            request: $request,
        );

        $disk = Storage::disk($this->backupService->backupDisk());
        $path = $this->backupService->backupDirectory().'/'.$filename;

        return $disk->download($path, $filename, [
            'Content-Type' => 'application/zip',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function destroy(Request $request, string $filename): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        if (! $this->backupService->isValidFilename($filename) || ! $this->backupService->backupExists($filename)) {
            abort(404);
        }

        $this->backupService->deleteBackup($filename);

        ActivityLogger::record(
            action: 'backup.deleted',
            description: "Menghapus arsip cadangan: {$filename}",
            subject: null,
            actor: $request->user(),
            request: $request,
        );

        return redirect()
            ->route('admin.backups.index')
            ->with('success', "Arsip {$filename} berhasil dihapus.");
    }

    public function prune(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->isAdmin(), 403);

        $pruned = $this->backupService->pruneOldBackups();

        if ($pruned === []) {
            return redirect()
                ->route('admin.backups.index')
                ->with('success', 'Tidak ada arsip lama yang perlu dibersihkan.');
        }

        ActivityLogger::record(
            action: 'backup.pruned',
            description: 'Membersihkan '.count($pruned).' arsip cadangan lama: '.implode(', ', $pruned),
            subject: null,
            actor: $request->user(),
            request: $request,
        );

        return redirect()
            ->route('admin.backups.index')
            ->with('success', 'Berhasil membersihkan '.count($pruned).' arsip lama.');
    }
}
