<?php

namespace Tests\Feature\Admin;

use App\Models\Book;
use App\Models\Category;
use App\Models\User;
use App\Services\BackupService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;
use ZipArchive;

class BackupManagementTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
        Storage::fake('local');
        Storage::fake('public');
    }

    public function test_non_admin_cannot_access_backup_routes(): void
    {
        $anggota = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();
        $sampleFile = 'backup-rtb-20261119-020000-abc123-db.zip';

        $this->get(route('admin.backups.index'))->assertRedirect(route('login'));

        foreach ([$anggota, $petugas] as $user) {
            $this->actingAs($user)->get(route('admin.backups.index'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.backups.store'), ['type' => 'db'])->assertForbidden();
            $this->actingAs($user)->post(route('admin.backups.prune'))->assertForbidden();
            $this->actingAs($user)->post(route('admin.backups.verify', $sampleFile))->assertForbidden();
            $this->actingAs($user)->get(route('admin.backups.download', $sampleFile))->assertForbidden();
            $this->actingAs($user)->delete(route('admin.backups.destroy', $sampleFile))->assertForbidden();
        }
    }

    public function test_admin_can_create_full_and_db_backups_and_excludes_ephemeral_token_data(): void
    {
        config(['library.backup.archive_password' => '']);

        $admin = User::factory()->admin()->create();
        $category = Category::factory()->create(['name' => 'Keamanan Informasi']);
        Book::factory()->create([
            'category_id' => $category->id,
            'title' => 'Panduan NIST SP 800-53',
            'cover_path' => 'covers/sample-cover.jpg',
        ]);

        Storage::disk('public')->put('covers/sample-cover.jpg', 'fake-jpeg-binary-content');

        // Masukkan data sensitif sementara di tabel ephemeral (sessions & password_reset_tokens)
        DB::table('sessions')->insert([
            'id' => 'ephemeral-session-id-secret-999',
            'user_id' => $admin->id,
            'ip_address' => '127.0.0.1',
            'user_agent' => 'PHPUnit',
            'payload' => 'sensitive-session-payload-token',
            'last_activity' => time(),
        ]);

        DB::table('password_reset_tokens')->insert([
            'email' => $admin->email,
            'token' => 'secret-reset-token-xyz-987',
            'created_at' => now(),
        ]);

        $response = $this->actingAs($admin)->post(route('admin.backups.store'), [
            'type' => 'full',
        ]);

        $response->assertRedirect(route('admin.backups.index'));
        $response->assertSessionHas('success');

        $service = app(BackupService::class);
        $backups = $service->listBackups();
        $this->assertCount(1, $backups);

        $latest = $backups[0];
        $this->assertSame('full', $latest['backup_type']);
        $this->assertSame(1, $latest['cover_files_count']);

        $report = $service->verifyBackup($latest['filename']);
        $this->assertTrue($report['valid']);
        $this->assertSame(2, $report['checked_files']); // database.sql + 1 file cover

        // Periksa isi database.sql tidak membocorkan isi tabel ephemeral
        $rawZip = Storage::disk('local')->get('backups/'.$latest['filename']);
        $tempZip = tempnam(sys_get_temp_dir(), 'rtb_test_');
        file_put_contents($tempZip, $rawZip);

        $zip = new ZipArchive;
        $zip->open($tempZip);
        $sql = (string) $zip->getFromName('database.sql');
        $cover = (string) $zip->getFromName('covers/sample-cover.jpg');
        $zip->close();
        @unlink($tempZip);

        $this->assertStringContainsString('Panduan NIST SP 800-53', $sql);
        $this->assertStringContainsString('DROP TABLE IF EXISTS `sessions`;', $sql);
        $this->assertStringNotContainsString('ephemeral-session-id-secret-999', $sql);
        $this->assertStringNotContainsString('sensitive-session-payload-token', $sql);
        $this->assertStringNotContainsString('secret-reset-token-xyz-987', $sql);
        $this->assertSame('fake-jpeg-binary-content', $cover);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'backup.created',
        ]);
    }

    public function test_backup_encrypts_payload_with_aes_256_when_password_is_configured(): void
    {
        config(['library.backup.archive_password' => 'NistCp98StrongAes256Key!']);

        $admin = User::factory()->admin()->create();
        Storage::disk('public')->put('covers/encrypted-cover.png', 'png-image-bytes');

        $service = app(BackupService::class);
        $result = $service->createBackup('full', $admin);

        $this->assertTrue($result['encrypted']);
        $this->assertSame('AES-256', $result['manifest']['encryption_algorithm']);

        // Pastikan database.sql di dalam ZIP benar-benar terenkripsi AES-256 dan tidak bisa dibaca tanpa password
        $rawZip = Storage::disk('local')->get($result['path']);
        $tempZip = tempnam(sys_get_temp_dir(), 'rtb_aes_');
        file_put_contents($tempZip, $rawZip);

        $zip = new ZipArchive;
        $zip->open($tempZip);
        $manifestStat = $zip->statName('manifest.json');
        $stat = $zip->statName('database.sql');
        $this->assertSame(ZipArchive::EM_AES_256, $manifestStat['encryption_method'] ?? null);
        $this->assertSame(ZipArchive::EM_AES_256, $stat['encryption_method'] ?? null);
        $this->assertFalse(@$zip->getFromName('manifest.json'));
        $this->assertFalse(@$zip->getFromName('database.sql'));
        $zip->close();
        @unlink($tempZip);

        // Verifikasi integritas melalui endpoint Admin berhasil dengan password yang benar
        $verifyRes = $this->actingAs($admin)->post(route('admin.backups.verify', $result['filename']));
        $verifyRes->assertRedirect(route('admin.backups.index'));
        $verifyRes->assertSessionHas('success');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'backup.verified',
        ]);

        // Jika password salah, verifikasi harus gagal
        config(['library.backup.archive_password' => 'WrongDecryptionKey123!']);
        $failedReport = $service->verifyBackup($result['filename']);
        $this->assertFalse($failedReport['valid']);
        $this->assertNotEmpty($failedReport['errors']);
    }

    public function test_integrity_verification_detects_tampered_sql_or_manifest_signature(): void
    {
        config(['library.backup.archive_password' => '']);

        $admin = User::factory()->admin()->create();
        $service = app(BackupService::class);
        $result = $service->createBackup('db', $admin);

        // Manipulasi isi database.sql di dalam arsip ZIP (simulasi data corruption / tampering)
        $rawZip = Storage::disk('local')->get($result['path']);
        $tempZip = tempnam(sys_get_temp_dir(), 'rtb_tamper_');
        file_put_contents($tempZip, $rawZip);

        $zip = new ZipArchive;
        $zip->open($tempZip);
        $zip->addFromString('database.sql', '-- RUANG TEMU BUKU SQL BACKUP DUMP -- CORRUPTED DATA');
        $zip->close();

        Storage::disk('local')->put($result['path'], file_get_contents($tempZip));
        @unlink($tempZip);

        $verifyRes = $this->actingAs($admin)->post(route('admin.backups.verify', $result['filename']));
        $verifyRes->assertRedirect(route('admin.backups.index'));
        $verifyRes->assertSessionHas('error');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'backup.verify_failed',
        ]);
    }

    public function test_retention_policy_prunes_excess_backups(): void
    {
        config([
            'library.backup.max_backups' => 2,
            'library.backup.retention_days' => 30,
            'library.backup.archive_password' => '',
        ]);

        $admin = User::factory()->admin()->create();
        $service = app(BackupService::class);

        $this->travel(-3)->hours();
        $b1 = $service->createBackup('db', $admin);

        $this->travel(1)->hours();
        $b2 = $service->createBackup('db', $admin);

        $this->travel(1)->hours();
        $b3 = $service->createBackup('db', $admin);
        $this->travelBack();

        $this->assertCount(3, $service->listBackups());

        $pruneRes = $this->actingAs($admin)->post(route('admin.backups.prune'));
        $pruneRes->assertRedirect(route('admin.backups.index'));
        $pruneRes->assertSessionHas('success');

        $remaining = array_column($service->listBackups(), 'filename');
        $this->assertCount(2, $remaining);
        $this->assertContains($b3['filename'], $remaining);
        $this->assertContains($b2['filename'], $remaining);
        $this->assertNotContains($b1['filename'], $remaining);
    }

    public function test_admin_can_download_and_delete_backup_while_invalid_filenames_return_404(): void
    {
        config(['library.backup.archive_password' => '']);

        $admin = User::factory()->admin()->create();
        $service = app(BackupService::class);
        $result = $service->createBackup('db', $admin);

        // Unduh arsip valid
        $downloadRes = $this->actingAs($admin)->get(route('admin.backups.download', $result['filename']));
        $downloadRes->assertOk();
        $downloadRes->assertHeader('X-Content-Type-Options', 'nosniff');

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'backup.downloaded',
        ]);

        // Percobaan manipulasi nama file / path traversal wajib ditolak 404
        $this->actingAs($admin)->get(route('admin.backups.download', '..%5C.env'))->assertNotFound();
        $this->actingAs($admin)->post(route('admin.backups.verify', 'arbitrary-file.zip'))->assertNotFound();
        $this->actingAs($admin)->delete(route('admin.backups.destroy', 'arbitrary-file.zip'))->assertNotFound();

        // Hapus arsip valid
        $deleteRes = $this->actingAs($admin)->delete(route('admin.backups.destroy', $result['filename']));
        $deleteRes->assertRedirect(route('admin.backups.index'));
        $this->assertFalse($service->backupExists($result['filename']));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $admin->id,
            'action' => 'backup.deleted',
        ]);
    }

    public function test_artisan_backup_and_verify_commands_succeed(): void
    {
        config(['library.backup.archive_password' => 'CliSecretKey123!']);

        $this->artisan('app:backup', ['--type' => 'db', '--prune' => true])
            ->assertExitCode(0);

        $this->artisan('app:backup-verify')
            ->assertExitCode(0);

        $this->assertDatabaseHas('activity_logs', [
            'action' => 'backup.created',
        ]);
        $this->assertDatabaseHas('activity_logs', [
            'action' => 'backup.verified',
        ]);
    }
}
