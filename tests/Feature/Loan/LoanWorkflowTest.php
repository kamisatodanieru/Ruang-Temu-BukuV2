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

class LoanWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);
    }

    public function test_member_can_request_available_book_and_pending_does_not_decrement_stock(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create([
            'total_stock' => 3,
            'available_stock' => 3,
        ]);

        $response = $this->actingAs($member)->post(route('member.loans.store'), [
            'book_id' => $book->id,
        ]);

        $loan = Loan::query()->where('member_id', $member->id)->firstOrFail();

        $response->assertRedirect(route('member.loans.show', $loan));
        $this->assertSame(LoanStatus::Pending, $loan->status);
        $this->assertNotNull($loan->requested_at);
        $this->assertSame(3, $book->fresh()->available_stock);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $member->id,
            'action' => 'loan.requested',
            'subject_id' => $loan->id,
        ]);
    }

    public function test_request_for_out_of_stock_or_soft_deleted_book_is_rejected(): void
    {
        $member = User::factory()->anggota()->create();
        $emptyBook = Book::factory()->outOfStock()->create(['total_stock' => 2]);
        $deletedBook = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);
        $deletedBook->delete();

        $resEmpty = $this->actingAs($member)
            ->from(route('catalog.show', $emptyBook))
            ->post(route('member.loans.store'), ['book_id' => $emptyBook->id]);

        $resEmpty->assertSessionHasErrors('loan');
        $this->assertDatabaseCount('loans', 0);

        $resDeleted = $this->actingAs($member)
            ->post(route('member.loans.store'), ['book_id' => $deletedBook->id]);

        $resDeleted->assertSessionHasErrors('loan');
        $this->assertDatabaseCount('loans', 0);
    }

    public function test_member_cannot_exceed_max_active_loans_quota_of_three(): void
    {
        $member = User::factory()->anggota()->create();

        // 2 Pending + 1 Borrowed = 3 transaksi aktif
        Loan::factory()->count(2)->create(['member_id' => $member->id]);
        Loan::factory()->borrowed()->create(['member_id' => $member->id]);

        $fourthBook = Book::factory()->create(['total_stock' => 4, 'available_stock' => 4]);

        $response = $this->actingAs($member)->post(route('member.loans.store'), [
            'book_id' => $fourthBook->id,
        ]);

        $response->assertSessionHasErrors('loan');
        $this->assertSame(3, $member->activeLoans()->count());
    }

    public function test_member_cannot_request_same_book_when_already_pending_or_borrowed(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 5, 'available_stock' => 5]);

        Loan::factory()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $response = $this->actingAs($member)->post(route('member.loans.store'), [
            'book_id' => $book->id,
        ]);

        $response->assertSessionHasErrors('loan');
        $this->assertSame(1, Loan::query()->where('member_id', $member->id)->count());
    }

    public function test_member_with_overdue_loan_is_blocked_from_creating_new_request(): void
    {
        $member = User::factory()->anggota()->create();
        Loan::factory()->overdue()->create(['member_id' => $member->id]);

        $newBook = Book::factory()->create(['total_stock' => 3, 'available_stock' => 3]);

        $response = $this->actingAs($member)->post(route('member.loans.store'), [
            'book_id' => $newBook->id,
        ]);

        $response->assertSessionHasErrors('loan');
        $this->assertSame(1, Loan::query()->where('member_id', $member->id)->count());
    }

    public function test_member_can_only_cancel_own_pending_loan_and_idor_is_blocked(): void
    {
        $memberA = User::factory()->anggota()->create();
        $memberB = User::factory()->anggota()->create();

        $pendingLoanA = Loan::factory()->create(['member_id' => $memberA->id]);
        $borrowedLoanA = Loan::factory()->borrowed()->create(['member_id' => $memberA->id]);
        $pendingLoanB = Loan::factory()->create(['member_id' => $memberB->id]);

        // IDOR test: Member A tidak dapat melihat atau membatalkan transaksi Member B
        $this->actingAs($memberA)->get(route('member.loans.show', $pendingLoanB))->assertForbidden();
        $this->actingAs($memberA)->patch(route('member.loans.cancel', $pendingLoanB))->assertForbidden();
        $this->assertSame(LoanStatus::Pending, $pendingLoanB->fresh()->status);

        // Member A tidak dapat membatalkan pinjamannya yang sudah Borrowed
        $resBorrowed = $this->actingAs($memberA)->patch(route('member.loans.cancel', $borrowedLoanA));
        $resBorrowed->assertSessionHasErrors('loan');
        $this->assertSame(LoanStatus::Borrowed, $borrowedLoanA->fresh()->status);

        // Member A berhasil membatalkan pinjamannya yang masih Pending
        $resCancel = $this->actingAs($memberA)->patch(route('member.loans.cancel', $pendingLoanA));
        $resCancel->assertRedirect(route('member.loans.index'));
        $this->assertSame(LoanStatus::Cancelled, $pendingLoanA->fresh()->status);
        $this->assertNotNull($pendingLoanA->fresh()->cancelled_at);
    }

    public function test_petugas_approval_updates_status_decrements_stock_and_sets_due_date(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);

        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        // Anggota dilarang mengakses endpoint approval
        $this->actingAs($member)->patch(route('staff.loans.approve', $loan))->assertForbidden();

        $response = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan))
            ->patch(route('staff.loans.approve', $loan));

        $response->assertRedirect(route('staff.loans.show', $loan));

        $loan->refresh();
        $book->refresh();

        $this->assertSame(LoanStatus::Borrowed, $loan->status);
        $this->assertSame($petugas->id, $loan->approved_by);
        $this->assertNotNull($loan->borrowed_at);
        $this->assertNotNull($loan->due_at);
        $this->assertSame(7, (int) round($loan->borrowed_at->diffInDays($loan->due_at)));
        $this->assertSame(1, $book->available_stock);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $petugas->id,
            'action' => 'loan.approved',
            'subject_id' => $loan->id,
        ]);
    }

    public function test_two_requests_for_last_stock_cannot_make_available_stock_negative(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member1 = User::factory()->anggota()->create();
        $member2 = User::factory()->anggota()->create();

        // Buku hanya tersisa 1 eksemplar tersedia
        $book = Book::factory()->create([
            'total_stock' => 1,
            'available_stock' => 1,
        ]);

        $loan1 = Loan::factory()->create([
            'member_id' => $member1->id,
            'book_id' => $book->id,
        ]);

        $loan2 = Loan::factory()->create([
            'member_id' => $member2->id,
            'book_id' => $book->id,
        ]);

        // Approval pertama berhasil menghabiskan stok terakhir
        $this->actingAs($petugas)->patch(route('staff.loans.approve', $loan1));
        $this->assertSame(LoanStatus::Borrowed, $loan1->fresh()->status);
        $this->assertSame(0, $book->fresh()->available_stock);

        // Approval kedua wajib gagal dan stok tidak boleh menjadi -1
        $secondApproval = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan2))
            ->patch(route('staff.loans.approve', $loan2));

        $secondApproval->assertSessionHasErrors('loan');
        $this->assertSame(LoanStatus::Pending, $loan2->fresh()->status);
        $this->assertSame(0, $book->fresh()->available_stock);
    }

    public function test_approval_is_rejected_if_member_becomes_inactive_before_approval(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 2]);

        $loan = Loan::factory()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $member->status = UserStatus::Inactive;
        $member->save();

        $response = $this->actingAs($petugas)->patch(route('staff.loans.approve', $loan));

        $response->assertSessionHasErrors('loan');
        $this->assertSame(LoanStatus::Pending, $loan->fresh()->status);
        $this->assertSame(2, $book->fresh()->available_stock);
    }

    public function test_petugas_can_reject_pending_loan_with_mandatory_reason(): void
    {
        $petugas = User::factory()->petugas()->create();
        $loan = Loan::factory()->create();

        // Tanpa alasan penolakan ditolak validasi
        $invalidRes = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan))
            ->patch(route('staff.loans.reject', $loan), [
                'rejection_reason' => '',
            ]);
        $invalidRes->assertSessionHasErrors('rejection_reason');
        $this->assertSame(LoanStatus::Pending, $loan->fresh()->status);

        // Dengan alasan valid berhasil ditolak
        $validRes = $this->actingAs($petugas)
            ->from(route('staff.loans.show', $loan))
            ->patch(route('staff.loans.reject', $loan), [
                'rejection_reason' => 'Buku fisik sedang diperbaiki sampulnya.',
            ]);

        $validRes->assertRedirect(route('staff.loans.show', $loan));

        $loan->refresh();
        $this->assertSame(LoanStatus::Rejected, $loan->status);
        $this->assertSame($petugas->id, $loan->rejected_by);
        $this->assertNotNull($loan->rejected_at);
        $this->assertSame('Buku fisik sedang diperbaiki sampulnya.', $loan->rejection_reason);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $petugas->id,
            'action' => 'loan.rejected',
            'subject_id' => $loan->id,
        ]);
    }
}
