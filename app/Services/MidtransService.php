<?php

namespace App\Services;

use Exception;
use Midtrans\Config;
use Midtrans\Notification;
use Midtrans\Snap;
use Midtrans\Transaction;

class MidtransService
{
    public function __construct()
    {
        $this->configure();
    }

    /**
     * Set Midtrans configuration from config file.
     */
    protected function configure(): void
    {
        Config::$serverKey = config('midtrans.server_key');
        Config::$isProduction = (bool) config('midtrans.is_production', false);
        Config::$isSanitized = (bool) config('midtrans.is_sanitized', true);
        Config::$is3ds = (bool) config('midtrans.is_3ds', true);
    }

    /**
     * Get Midtrans Snap Token for transaction.
     *
     * @param  array<string, mixed>  $params
     * @throws Exception
     */
    public function createSnapToken(array $params): string
    {
        return Snap::getSnapToken($params);
    }

    /**
     * Create Snap Transaction and get redirect URL / token.
     *
     * @param  array<string, mixed>  $params
     * @return object
     * @throws Exception
     */
    public function createTransaction(array $params): object
    {
        return Snap::createTransaction($params);
    }

    /**
     * Handle incoming Midtrans HTTP Notification.
     *
     * @throws Exception
     */
    public function handleNotification(): Notification
    {
        return new Notification();
    }

    /**
     * Check transaction status by Order ID.
     *
     * @throws Exception
     */
    public function getTransactionStatus(string $orderId): object
    {
        return Transaction::status($orderId);
    }

    /**
     * Cancel transaction by Order ID.
     *
     * @throws Exception
     */
    public function cancelTransaction(string $orderId): object
    {
        return Transaction::cancel($orderId);
    }

    /**
     * Expire transaction by Order ID.
     *
     * @throws Exception
     */
    public function expireTransaction(string $orderId): object
    {
        return Transaction::expire($orderId);
    }

    /**
     * Verify Midtrans notification signature key.
     * Signature format: SHA512(order_id + status_code + gross_amount + ServerKey)
     */
    public function verifySignature(string $orderId, string $statusCode, string $grossAmount, string $signatureKey): bool
    {
        $serverKey = trim((string) config('midtrans.server_key'));
        if ($serverKey === '' || trim($signatureKey) === '') {
            return false;
        }

        $expectedSignature = hash('sha512', $orderId . $statusCode . $grossAmount . $serverKey);

        return hash_equals($expectedSignature, $signatureKey);
    }

    /**
     * Get Client Key for frontend integration (Snap JS).
     */
    public function getClientKey(): string
    {
        return (string) config('midtrans.client_key');
    }

    /**
     * Get Snap JS Script URL depending on environment.
     */
    public function getSnapJsUrl(): string
    {
        return config('midtrans.is_production')
            ? 'https://app.midtrans.com/snap/snap.js'
            : 'https://app.sandbox.midtrans.com/snap/snap.js';
    }
}
