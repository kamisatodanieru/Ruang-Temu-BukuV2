<?php

namespace Tests\Feature\Admin;

use App\Enums\LoanStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use App\Services\ActivityLogger;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReportAndAuditLogTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_non_admin_cannot_access_reports_or_audit_logs(): void
    {
        $anggota = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();

        $this->get(route('admin.reports.index'))->assertRedirect(route('login'));
        $this->get(route('admin.audit-logs.index'))->assertRedirect(route('login'));

        $this->actingAs($anggota)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($anggota)->get(route('admin.reports.export'))->assertForbidden();
        $this->actingAs($anggota)->get(route('admin.audit-logs.index'))->assertForbidden();

        $this->actingAs($petugas)->get(route('admin.reports.index'))->assertForbidden();
        $this->actingAs($petugas)->get(route('admin.reports.export'))->assertForbidden();
        $this->actingAs($petugas)->get(route('admin.audit-logs.index'))->assertForbidden();
    }

    public function test_admin_can_filter_loan_reports_by_date_and_status(): void
    {
        $admin = User::factory()->admin()->create();

        $bookOld = Book::factory()->create(['title' => 'Buku Bulan Lalu']);
        $bookRecent = Book::factory()->create(['title' => 'Buku Hari Ini']);

        Loan::factory()->returned()->create([
            'book_id' => $bookOld->id,
            'requested_at' => now()->subDays(20),
        ]);

        Loan::factory()->borrowed()->create([
            'book_id' => $bookRecent->id,
            'requested_at' => now(),
        ]);

        // Filter berdasarkan status borrowed
        $resStatus = $this->actingAs($admin)->get(route('admin.reports.index', [
            'status' => LoanStatus::Borrowed->value,
        ]));
        $resStatus->assertOk();
        $resStatus->assertSee('Buku Hari Ini');
        $resStatus->assertDontSee('Buku Bulan Lalu');

        // Filter berdasarkan rentang tanggal 5 hari terakhir
        $resDate = $this->actingAs($admin)->get(route('admin.reports.index', [
            'start_date' => now()->subDays(5)->toDateString(),
            'end_date' => now()->toDateString(),
        ]));
        $resDate->assertOk();
        $resDate->assertSee('Buku Hari Ini');
        $resDate->assertDontSee('Buku Bulan Lalu');
    }

    public function test_csv_export_neutralizes_formula_injection_and_excludes_sensitive_data(): void
    {
        $admin = User::factory()->admin()->create();
        $member = User::factory()->anggota()->create([
            'name' => '=cmd|\' /C calc\'!A0',
            'password' => 'SuperSecretPassword123!',
        ]);
        $book = Book::factory()->create([
            'title' => '+SUM(1,2) Buku Injeksi',
        ]);

        Loan::factory()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'status' => LoanStatus::Rejected,
            'rejection_reason' => '@IMPORTXML("http://evil.local")',
        ]);

        $response = $this->actingAs($admin)->get(route('admin.reports.export'));

        $response->assertOk();
        $csvContent = $response->streamedContent();

        $this->assertStringContainsString("'=cmd|' /C calc'!A0", $csvContent);
        $this->assertStringContainsString("'+SUM(1,2) Buku Injeksi", $csvContent);
        $this->assertStringContainsString("'@IMPORTXML", $csvContent);
        $this->assertStringNotContainsString($member->password, $csvContent);
    }

    public function test_admin_can_view_and_filter_audit_logs(): void
    {
        $admin = User::factory()->admin()->create();

        ActivityLogger::record(
            action: 'book.created',
            description: 'Menambahkan buku Algoritma Pemrograman',
            subject: null,
            actor: $admin,
        );

        ActivityLogger::record(
            action: 'user.status_changed',
            description: 'Mengubah status akun anggota',
            subject: null,
            actor: $admin,
        );

        $response = $this->actingAs($admin)->get(route('admin.audit-logs.index', [
            'action' => 'book.created',
        ]));

        $response->assertOk();
        $response->assertSee('Menambahkan buku Algoritma Pemrograman');
        $response->assertDontSee('Mengubah status akun anggota');
    }
}
