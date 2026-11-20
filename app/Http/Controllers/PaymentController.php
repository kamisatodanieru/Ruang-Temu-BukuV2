<?php

namespace App\Http\Controllers;

use App\Models\Fine;
use App\Services\FinePaymentService;
use App\Services\MidtransService;
use Exception;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class PaymentController extends Controller
{
    public function __construct(
        protected MidtransService $midtransService,
        protected FinePaymentService $finePaymentService
    ) {}

    /**
     * Buat transaksi Midtrans Snap untuk pembayaran denda anggota.
     */
    public function payFine(Request $request, Fine $fine): JsonResponse
    {
        try {
            $payment = $this->finePaymentService->initiatePayment(
                fine: $fine,
                actor: $request->user(),
                request: $request,
            );

            return response()->json([
                'snap_token' => $payment->snap_token,
                'order_id' => $payment->order_id,
                'gross_amount' => $payment->gross_amount,
            ]);
        } catch (ValidationException|HttpExceptionInterface $e) {
            throw $e;
        } catch (Exception $e) {
            Log::error('Midtrans Snap Error: '.$e->getMessage());

            return response()->json([
                'message' => 'Gagal menghubungi layanan pembayaran. Silakan coba beberapa saat lagi.',
            ], 500);
        }
    }

    /**
     * Handle HTTP Notification / Webhook from Midtrans.
     */
    public function handleNotification(Request $request): JsonResponse
    {
        try {
            $payload = $request->all();
            $orderId = $payload['order_id'] ?? null;
            $statusCode = $payload['status_code'] ?? null;
            $grossAmount = $payload['gross_amount'] ?? null;
            $signatureKey = $payload['signature_key'] ?? null;
            $transactionStatus = $payload['transaction_status'] ?? null;
            $fraudStatus = $payload['fraud_status'] ?? null;
            $paymentType = $payload['payment_type'] ?? null;

            // Validasi kelengkapan data dasar
            if (! $orderId || ! $statusCode || ! $grossAmount || ! $signatureKey) {
                Log::warning('Midtrans Webhook: Payload tidak lengkap', [
                    'order_id' => is_scalar($orderId) ? (string) $orderId : null,
                    'ip' => $request->ip(),
                ]);

                return response()->json(['message' => 'Invalid payload data'], 400);
            }

            // Verifikasi signature key
            if (! $this->midtransService->verifySignature((string) $orderId, (string) $statusCode, (string) $grossAmount, (string) $signatureKey)) {
                Log::warning("Midtrans Webhook: Signature key tidak valid untuk Order ID {$orderId}");

                return response()->json(['message' => 'Invalid signature key'], 403);
            }

            Log::info("Midtrans Webhook received: Order ID {$orderId}, Status: {$transactionStatus}, Fraud: {$fraudStatus}, Type: {$paymentType}");

            // Perbarui status pembayaran & tagihan denda di database
            $this->finePaymentService->handleWebhookNotification($payload);

            return response()->json(['message' => 'Notification handled successfully']);
        } catch (Exception $e) {
            Log::error('Midtrans Webhook Error: '.$e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json(['message' => 'Error handling notification'], 500);
        }
    }
}
