<?php

namespace App\Services\Payment;

use App\Exceptions\PaymentGatewayNotConfiguredException;
use App\Models\Booking;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class RazorpayPaymentGateway implements PaymentGatewayInterface
{
    public function driverName(): string
    {
        return 'razorpay';
    }

    public function isConfigured(): bool
    {
        $keyId = (string) config('spaceworq.payment.razorpay.key_id', '');
        $keySecret = (string) config('spaceworq.payment.razorpay.key_secret', '');

        return $keyId !== '' && $keySecret !== '';
    }

    public function createOrder(Booking $booking, string $idempotencyKey): array
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException();
        }

        $keyId = (string) config('spaceworq.payment.razorpay.key_id');
        $keySecret = (string) config('spaceworq.payment.razorpay.key_secret');
        $amountPaise = (int) round(((float) $booking->total_amount) * 100);

        $response = Http::withBasicAuth($keyId, $keySecret)
            ->timeout(15)
            ->post('https://api.razorpay.com/v1/orders', [
                'amount' => $amountPaise,
                'currency' => $booking->currency,
                'receipt' => $booking->booking_reference,
                'notes' => [
                    'booking_id' => (string) $booking->id,
                    'idempotency_key' => $idempotencyKey,
                ],
            ]);

        if (! $response->successful()) {
            throw new RuntimeException('Unable to create Razorpay order.');
        }

        $body = $response->json();

        return [
            'gateway_order_id' => (string) ($body['id'] ?? ''),
            'amount' => $booking->total_amount,
            'currency' => $booking->currency,
            'key_id' => $keyId,
            'raw' => [
                'id' => $body['id'] ?? null,
                'status' => $body['status'] ?? null,
                'amount' => $body['amount'] ?? null,
            ],
        ];
    }

    public function verifyPaymentSignature(array $payload): bool
    {
        if (! $this->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException();
        }

        $orderId = (string) ($payload['gateway_order_id'] ?? $payload['razorpay_order_id'] ?? '');
        $paymentId = (string) ($payload['gateway_payment_id'] ?? $payload['razorpay_payment_id'] ?? '');
        $signature = (string) ($payload['signature'] ?? $payload['razorpay_signature'] ?? '');

        if ($orderId === '' || $paymentId === '' || $signature === '') {
            return false;
        }

        $expected = hash_hmac('sha256', "{$orderId}|{$paymentId}", (string) config('spaceworq.payment.razorpay.key_secret'));

        return hash_equals($expected, $signature);
    }

    public function verifyWebhookSignature(string $rawPayload, ?string $signature): bool
    {
        $webhookSecret = (string) config('spaceworq.payment.razorpay.webhook_secret', '');
        if ($webhookSecret === '' || ! $signature) {
            return false;
        }

        $expected = hash_hmac('sha256', $rawPayload, $webhookSecret);

        return hash_equals($expected, $signature);
    }

    public function parseWebhookEvent(array $payload): array
    {
        $entity = $payload['payload']['payment']['entity'] ?? [];

        return [
            'event_id' => (string) ($payload['event_id'] ?? $payload['id'] ?? hash('sha256', json_encode($payload))),
            'event_type' => (string) ($payload['event'] ?? 'unknown'),
            'gateway_order_id' => isset($entity['order_id']) ? (string) $entity['order_id'] : null,
            'gateway_payment_id' => isset($entity['id']) ? (string) $entity['id'] : null,
            'status' => isset($entity['status']) ? (string) $entity['status'] : null,
        ];
    }
}
