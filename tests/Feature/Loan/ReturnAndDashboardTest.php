<?php

namespace Tests\Feature\Loan;

use App\Enums\LoanStatus;
use App\Enums\UserStatus;
use App\Models\Book;
use App\Models\Loan;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReturnAndDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_petugas_can_confirm_return_and_stock_increments_exactly_once(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create([
            'total_stock' => 4,
            'available_stock' => 3,
        ]);

        $loan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        // Anggota dilarang mengonfirmasi pengembalian sendiri
        $this->actingAs($member)->patch(route('staff.loans.return', $loan))->assertForbidden();

        // Petugas mengonfirmasi pengembalian pertama kali -> berhasil
        $resFirst = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan))
            ->patch(route('staff.loans.return', $loan));

        $resFirst->assertRedirect(route('staff.loans.show', $loan));

        $loan->refresh();
        $book->refresh();

        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertNotNull($loan->returned_at);
        $this->assertSame($petugas->id, $loan->returned_confirmed_by);
        $this->assertSame(4, $book->available_stock);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $petugas->id,
            'action' => 'loan.returned',
            'subject_id' => $loan->id,
        ]);

        // Percobaan pengembalian kedua kali pada transaksi yang sama ditolak & stok tidak bertambah ganda
        $resSecond = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan))
            ->patch(route('staff.loans.return', $loan));

        $resSecond->assertSessionHasErrors('loan');
        $this->assertSame(4, $book->fresh()->available_stock);
    }

    public function test_pending_or_rejected_loan_cannot_be_returned(): void
    {
        $petugas = User::factory()->petugas()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);

        $pendingLoan = Loan::factory()->create([
            'book_id' => $book->id,
        ]);

        $res = $this->actingAs($petugas)->patch(route('staff.loans.return', $pendingLoan));

        $res->assertSessionHasErrors('loan');
        $this->assertSame(LoanStatus::Pending, $pendingLoan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    public function test_petugas_can_process_return_even_when_member_account_is_inactive(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

        $loan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        // Akun anggota dinonaktifkan saat masih meminjam buku
        $member->status = UserStatus::Inactive;
        $member->save();

        $response = $this->actingAs($petugas)->patch(route('staff.loans.return', $loan));

        $response->assertSessionHasNoErrors();
        $this->assertSame(LoanStatus::Returned, $loan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    public function test_dashboards_display_role_specific_data_and_isolate_member_loans(): void
    {
        $admin = User::factory()->admin()->create();
        $petugas = User::factory()->petugas()->create();
        $memberA = User::factory()->anggota()->create(['name' => 'Anggota Satu']);
        $memberB = User::factory()->anggota()->create(['name' => 'Anggota Dua']);

        $bookA = Book::factory()->create(['title' => 'Buku Pinjaman Anggota A']);
        $bookB = Book::factory()->create(['title' => 'Buku Rahasia Anggota B']);

        Loan::factory()->overdue()->state([
            'status' => LoanStatus::ReturnPending,
            'return_requested_at' => now()->subHour(),
        ])->create([
            'member_id' => $memberA->id,
            'book_id' => $bookA->id,
        ]);

        Loan::factory()->borrowed()->create([
            'member_id' => $memberB->id,
            'book_id' => $bookB->id,
        ]);

        // Dashboard Anggota A hanya menampilkan buku milik Anggota A dan status overdue-nya
        $resMemberA = $this->actingAs($memberA)->get(route('dashboard'));
        $resMemberA->assertOk();
        $resMemberA->assertSee('Buku Pinjaman Anggota A');
        $resMemberA->assertSee('Terlambat');
        $resMemberA->assertDontSee('Buku Rahasia Anggota B');

        // Dashboard Petugas menampilkan antrean pengembalian operasional
        $resPetugas = $this->actingAs($petugas)->get(route('dashboard'));
        $resPetugas->assertOk();
        $resPetugas->assertSee('Pengembalian Perlu Dikonfirmasi');
        $resPetugas->assertSee('Buku Pinjaman Anggota A');

        // Dashboard Admin menampilkan ringkasan koleksi & transaksi
        $resAdmin = $this->actingAs($admin)->get(route('dashboard'));
        $resAdmin->assertOk();
        $resAdmin->assertSee('Transaksi Peminjaman Terbaru');
        $resAdmin->assertSee('Aktivitas Sistem Terbaru');
    }

    public function test_member_can_request_return_and_petugas_can_confirm_return(): void
    {
        $member = User::factory()->anggota()->create();
        $petugas = User::factory()->petugas()->create();
        $book = Book::factory()->create(['total_stock' => 5, 'available_stock' => 4]);

        $loan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        // Pada halaman staf, tombol Konfirmasi Kembali belum tampil untuk status Borrowed
        $resStaffBefore = $this->actingAs($petugas)->get(route('staff.loans.index'));
        $resStaffBefore->assertOk();
        $resStaffBefore->assertDontSee('Konfirmasi Kembali');

        // Anggota mengajukan pengembalian
        $resRequest = $this->actingAs($member)
            ->from(route('member.loans.index'))
            ->patch(route('member.loans.request-return', $loan));

        $resRequest->assertRedirect(route('member.loans.index'));
        $resRequest->assertSessionHas('success');

        $loan->refresh();
        $this->assertSame(LoanStatus::ReturnPending, $loan->status);
        $this->assertNotNull($loan->return_requested_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $member->id,
            'action' => 'loan.return_requested',
            'subject_id' => $loan->id,
        ]);

        // Setelah diajukan, tombol Konfirmasi Kembali tampil di halaman staf
        $resStaffAfter = $this->actingAs($petugas)->get(route('staff.loans.index'));
        $resStaffAfter->assertOk();
        $resStaffAfter->assertSee('Konfirmasi Kembali');

        // Petugas mengonfirmasi pengembalian
        $resConfirm = $this->actingAs($petugas)
            ->from(route('staff.loans.index'))
            ->patch(route('staff.loans.return', $loan));

        $resConfirm->assertRedirect(route('staff.loans.index'));

        $loan->refresh();
        $book->refresh();
        $this->assertSame(LoanStatus::Returned, $loan->status);
        $this->assertSame(5, $book->available_stock);
    }

    public function test_member_cannot_request_return_for_other_members_loan(): void
    {
        $memberA = User::factory()->anggota()->create();
        $memberB = User::factory()->anggota()->create();
        $loanA = Loan::factory()->borrowed()->create(['member_id' => $memberA->id]);

        $this->actingAs($memberB)
            ->patch(route('member.loans.request-return', $loanA))
            ->assertForbidden();

        $this->assertSame(LoanStatus::Borrowed, $loanA->fresh()->status);
    }

    public function test_member_cannot_request_return_for_pending_loan(): void
    {
        $member = User::factory()->anggota()->create();
        $loan = Loan::factory()->create(['member_id' => $member->id, 'status' => LoanStatus::Pending]);

        $res = $this->actingAs($member)->patch(route('member.loans.request-return', $loan));
        $res->assertSessionHasErrors('loan');

        $this->assertSame(LoanStatus::Pending, $loan->fresh()->status);
    }

    public function test_staff_returns_filter_includes_return_pending_and_borrowed_and_shows_live_overdue_estimate(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();

        $bookBorrowed = Book::factory()->create(['title' => 'Buku Masih Dipinjam']);
        $bookReturnPending = Book::factory()->create(['title' => 'Buku Diajukan Kembali']);
        $bookPendingApproval = Book::factory()->create(['title' => 'Buku Baru Diajukan']);

        $overdueBorrowedLoan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $bookBorrowed->id,
            'borrowed_at' => now()->subDays(11),
            'due_at' => now()->subDays(4)->addHour(),
        ]);

        Loan::factory()->borrowed()->state([
            'status' => LoanStatus::ReturnPending,
            'return_requested_at' => now()->subMinutes(15),
        ])->create([
            'member_id' => $member->id,
            'book_id' => $bookReturnPending->id,
        ]);

        Loan::factory()->create([
            'member_id' => $member->id,
            'book_id' => $bookPendingApproval->id,
        ]);

        // Filter menu Pengembalian (?status=returns) menampilkan ReturnPending (urutan atas) sekaligus Borrowed, tanpa Pending
        $resReturns = $this->actingAs($petugas)->get(route('staff.loans.index', ['status' => 'returns']));
        $resReturns->assertOk();
        $resReturns->assertSeeInOrder(['Buku Diajukan Kembali', 'Buku Masih Dipinjam']);
        $resReturns->assertDontSee('Buku Baru Diajukan');

        // Halaman detail staf langsung menampilkan estimasi denda 4 hari (Rp 4.000) walau anggota belum membuka halaman detail
        $resShow = $this->actingAs($petugas)->get(route('staff.loans.show', $overdueBorrowedLoan));
        $resShow->assertOk();
        $resShow->assertSee('Estimasi Denda Keterlambatan Saat Ini');
        $resShow->assertSee('Rp 4.000');
    }
}
