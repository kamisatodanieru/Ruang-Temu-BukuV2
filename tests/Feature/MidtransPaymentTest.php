<?php

namespace Tests\Feature;

use App\Enums\FineStatus;
use App\Enums\PaymentStatus;
use App\Models\Book;
use App\Models\Fine;
use App\Models\Loan;
use App\Models\Payment;
use App\Models\User;
use App\Services\MidtransService;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery\MockInterface;
use Tests\TestCase;

class MidtransPaymentTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(RoleSeeder::class);

        config([
            'midtrans.server_key' => 'SB-Mid-server-test-key-12345',
            'midtrans.client_key' => 'SB-Mid-client-test-key-67890',
            'midtrans.is_production' => false,
            'library.fine_per_day' => 1000,
        ]);
    }

    public function test_midtrans_service_configuration_and_urls(): void
    {
        $service = app(MidtransService::class);

        $this->assertSame('SB-Mid-client-test-key-67890', $service->getClientKey());
        $this->assertSame('https://app.sandbox.midtrans.com/snap/snap.js', $service->getSnapJsUrl());

        config(['midtrans.is_production' => true]);
        $serviceProd = app(MidtransService::class);
        $this->assertSame('https://app.midtrans.com/snap/snap.js', $serviceProd->getSnapJsUrl());
    }

    public function test_midtrans_signature_verification(): void
    {
        $service = app(MidtransService::class);
        $orderId = 'ORDER-1001';
        $statusCode = '200';
        $grossAmount = '50000.00';
        $serverKey = 'SB-Mid-server-test-key-12345';

        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);
        $invalidSignature = 'invalid-signature-hash';

        $this->assertTrue($service->verifySignature($orderId, $statusCode, $grossAmount, $validSignature));
        $this->assertFalse($service->verifySignature($orderId, $statusCode, $grossAmount, $invalidSignature));

        // Jika server_key kosong, verifikasi wajib menolak meskipun hash dihitung dengan string kosong
        config(['midtrans.server_key' => '']);
        $forgedWithEmptyKey = hash('sha512', $orderId.$statusCode.$grossAmount.'');
        $this->assertFalse($service->verifySignature($orderId, $statusCode, $grossAmount, $forgedWithEmptyKey));
    }

    public function test_notification_webhook_rejects_incomplete_payload(): void
    {
        $response = $this->postJson(route('midtrans.notification'), [
            'order_id' => 'ORDER-1001',
        ]);

        $response->assertStatus(400)
            ->assertJson([
                'message' => 'Invalid payload data',
            ]);
    }

    public function test_notification_webhook_rejects_invalid_signature(): void
    {
        $payload = [
            'order_id' => 'ORDER-1001',
            'status_code' => '200',
            'gross_amount' => '50000.00',
            'signature_key' => 'invalid-signature',
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
        ];

        $response = $this->postJson(route('midtrans.notification'), $payload);

        $response->assertStatus(403)
            ->assertJson([
                'message' => 'Invalid signature key',
            ]);
    }

    public function test_returning_overdue_loan_automatically_creates_fine(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

        $overdueLoan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3)->addHour(),
        ]);

        $this->actingAs($petugas)
            ->patch(route('staff.loans.return', $overdueLoan))
            ->assertSessionHasNoErrors();

        $fine = Fine::query()->where('loan_id', $overdueLoan->id)->first();

        $this->assertNotNull($fine);
        $this->assertSame($member->id, $fine->member_id);
        $this->assertSame(3, $fine->overdue_days);
        $this->assertSame(1000, $fine->fine_per_day);
        $this->assertSame(3000, $fine->total_amount);
        $this->assertSame(FineStatus::Unpaid, $fine->status);
    }

    public function test_member_can_initiate_midtrans_payment_and_webhook_settles_fine(): void
    {
        $member = User::factory()->anggota()->create();
        $otherMember = User::factory()->anggota()->create();
        $book = Book::factory()->create(['title' => 'Filosofi Teras']);
        $loan = Loan::factory()->returned()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
        ]);

        $fine = Fine::query()->create([
            'loan_id' => $loan->id,
            'member_id' => $member->id,
            'overdue_days' => 5,
            'fine_per_day' => 1000,
            'total_amount' => 5000,
            'status' => FineStatus::Unpaid,
        ]);

        $this->partialMock(MidtransService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createSnapToken')
                ->once()
                ->andReturn('dummy-snap-token-xyz-123');
        });

        // Anggota lain tidak boleh membayar denda milik member ini (IDOR protection)
        $this->actingAs($otherMember)
            ->postJson(route('member.fines.pay', $fine))
            ->assertForbidden();

        // Pemilik denda memulai pembayaran Snap
        $payResponse = $this->actingAs($member)
            ->postJson(route('member.fines.pay', $fine));

        $payResponse->assertOk()
            ->assertJson([
                'snap_token' => 'dummy-snap-token-xyz-123',
                'gross_amount' => 5000,
            ]);

        $orderId = $payResponse->json('order_id');
        $this->assertNotEmpty($orderId);

        $fine->refresh();
        $this->assertSame(FineStatus::PendingPayment, $fine->status);

        $payment = Payment::query()->where('order_id', $orderId)->first();
        $this->assertNotNull($payment);
        $this->assertSame(PaymentStatus::Pending, $payment->status);

        // Simulasikan Webhook Settlement dari Midtrans
        $statusCode = '200';
        $grossAmount = '5000.00';
        $serverKey = 'SB-Mid-server-test-key-12345';
        $validSignature = hash('sha512', $orderId.$statusCode.$grossAmount.$serverKey);

        $webhookPayload = [
            'order_id' => $orderId,
            'status_code' => $statusCode,
            'gross_amount' => $grossAmount,
            'signature_key' => $validSignature,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'MID-TRX-998877',
        ];

        $webhookResponse = $this->postJson(route('midtrans.notification'), $webhookPayload);

        $webhookResponse->assertOk()
            ->assertJson([
                'message' => 'Notification handled successfully',
            ]);

        $payment->refresh();
        $fine->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        $this->assertSame('qris', $payment->payment_type);
        $this->assertSame('MID-TRX-998877', $payment->transaction_id);
        $this->assertNotNull($payment->settled_at);

        $this->assertSame(FineStatus::Paid, $fine->status);
        $this->assertNotNull($fine->paid_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $member->id,
            'action' => 'fine.paid',
            'subject_id' => $fine->id,
        ]);
    }

    public function test_returning_overdue_loan_updates_stale_fine_from_earlier_day(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

        $overdueLoan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(8),
            'due_at' => now()->subDay()->addHour(),
        ]);

        // Hari ke-1 overdue: anggota membuka halaman detail sehingga Fine tercatat 1 hari (Rp 1.000)
        $this->actingAs($member)->get(route('member.loans.show', $overdueLoan))->assertOk();

        $fine = Fine::query()->where('loan_id', $overdueLoan->id)->firstOrFail();
        $this->assertSame(1, $fine->overdue_days);
        $this->assertSame(1000, $fine->total_amount);

        // Waktu berjalan 4 hari lagi (total 5 hari overdue) lalu buku dikembalikan ke petugas
        $this->travel(4)->days();

        $this->actingAs($petugas)
            ->patch(route('staff.loans.return', $overdueLoan))
            ->assertSessionHasNoErrors();

        $fine->refresh();
        $this->assertSame(5, $fine->overdue_days);
        $this->assertSame(5000, $fine->total_amount);
        $this->assertSame(FineStatus::Unpaid, $fine->status);
    }

    public function test_replayed_webhook_from_prior_payment_does_not_settle_new_residual_fine_after_return(): void
    {
        $petugas = User::factory()->petugas()->create();
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

        $overdueLoan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(10),
            'due_at' => now()->subDays(3)->addHour(),
        ]);

        // Hari ke-3 overdue: anggota melihat detail & membayar denda Rp 3.000 sebelum mengembalikan buku
        $this->actingAs($member)->get(route('member.loans.show', $overdueLoan))->assertOk();
        $fine = Fine::query()->where('loan_id', $overdueLoan->id)->firstOrFail();

        $this->partialMock(MidtransService::class, function (MockInterface $mock) {
            $mock->shouldReceive('createSnapToken')
                ->once()
                ->andReturn('dummy-snap-token-initial');
        });

        $payRes = $this->actingAs($member)->postJson(route('member.fines.pay', $fine));
        $firstOrderId = (string) $payRes->json('order_id');
        $serverKey = 'SB-Mid-server-test-key-12345';
        $firstSig = hash('sha512', $firstOrderId.'200'.'3000.00'.$serverKey);

        $firstWebhookPayload = [
            'order_id' => $firstOrderId,
            'status_code' => '200',
            'gross_amount' => '3000.00',
            'signature_key' => $firstSig,
            'transaction_status' => 'settlement',
            'fraud_status' => 'accept',
            'payment_type' => 'qris',
            'transaction_id' => 'MID-TRX-FIRST',
        ];

        $this->postJson(route('midtrans.notification'), $firstWebhookPayload)->assertOk();
        $this->assertSame(FineStatus::Paid, $fine->fresh()->status);

        // Anggota baru mengembalikan buku 2 hari kemudian (total 5 hari overdue -> sisa 2 hari = Rp 2.000)
        $this->travel(2)->days();
        $this->actingAs($petugas)->patch(route('staff.loans.return', $overdueLoan))->assertSessionHasNoErrors();

        $fine->refresh();
        $this->assertSame(FineStatus::Unpaid, $fine->status);
        $this->assertSame(2, $fine->overdue_days);
        $this->assertSame(2000, $fine->total_amount);

        // Replay / pengiriman ulang webhook pertama (Rp 3.000) TIDAK boleh melunasi denda selisih baru (Rp 2.000)
        $this->postJson(route('midtrans.notification'), $firstWebhookPayload)->assertOk();

        $fine->refresh();
        $this->assertSame(FineStatus::Unpaid, $fine->status);
        $this->assertSame(2000, $fine->total_amount);
    }

    public function test_loan_show_syncs_pending_midtrans_payment_before_day_rollover_recalculation(): void
    {
        $member = User::factory()->anggota()->create();
        $book = Book::factory()->create(['total_stock' => 2, 'available_stock' => 1]);

        $overdueLoan = Loan::factory()->borrowed()->create([
            'member_id' => $member->id,
            'book_id' => $book->id,
            'borrowed_at' => now()->subDays(8),
            'due_at' => now()->subDay()->addHour(),
        ]);

        $this->actingAs($member)->get(route('member.loans.show', $overdueLoan))->assertOk();
        $fine = Fine::query()->where('loan_id', $overdueLoan->id)->firstOrFail();

        $payment = Payment::query()->create([
            'order_id' => 'FINE-ROLLOVER-1001',
            'fine_id' => $fine->id,
            'user_id' => $member->id,
            'gross_amount' => 1000,
            'snap_token' => 'snap-rollover-token',
            'status' => PaymentStatus::Pending,
        ]);
        $fine->status = FineStatus::PendingPayment;
        $fine->save();

        // Waktu melewati batas pergantian hari ke-2 (Rp 2.000), sementara pembayaran Rp 1.000 sudah settlement di Midtrans
        $this->travel(1)->days();

        $this->partialMock(MidtransService::class, function (MockInterface $mock) {
            $mock->shouldReceive('getTransactionStatus')
                ->once()
                ->with('FINE-ROLLOVER-1001')
                ->andReturn((object) [
                    'order_id' => 'FINE-ROLLOVER-1001',
                    'transaction_status' => 'settlement',
                    'fraud_status' => 'accept',
                    'gross_amount' => '1000.00',
                    'payment_type' => 'qris',
                    'transaction_id' => 'MID-ROLLOVER-1',
                ]);
        });

        $this->actingAs($member)->get(route('member.loans.show', $overdueLoan))->assertOk();

        $payment->refresh();
        $fine->refresh();

        $this->assertSame(PaymentStatus::Paid, $payment->status);
        // Karena 1 hari (Rp 1.000) sudah dibayar dari total 2 hari (Rp 2.000), sisa tagihan hanya 1 hari (Rp 1.000)
        $this->assertSame(1, $fine->overdue_days);
        $this->assertSame(1000, $fine->total_amount);
        $this->assertSame(FineStatus::Unpaid, $fine->status);
    }

    public function test_member_with_unpaid_fine_on_returned_loan_cannot_borrow_new_book(): void
    {
        $member = User::factory()->anggota()->create();
        $returnedLoan = Loan::factory()->returned()->create(['member_id' => $member->id]);

        Fine::query()->create([
            'loan_id' => $returnedLoan->id,
            'member_id' => $member->id,
            'overdue_days' => 2,
            'fine_per_day' => 1000,
            'total_amount' => 2000,
            'status' => FineStatus::Unpaid,
        ]);

        $newBook = Book::factory()->create(['total_stock' => 3, 'available_stock' => 3]);

        $res = $this->actingAs($member)->post(route('member.loans.store'), [
            'book_id' => $newBook->id,
        ]);

        $res->assertSessionHasErrors('loan');
        $this->assertSame(0, $member->activeLoans()->count());
    }
}
