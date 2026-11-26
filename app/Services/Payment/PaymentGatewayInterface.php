<?php

namespace App\Services\Payment;

use App\Models\Booking;

interface PaymentGatewayInterface
{
    public function driverName(): string;

    public function isConfigured(): bool;

    public function createOrder(Booking $booking, string $idempotencyKey): array;

    public function verifyPaymentSignature(array $payload): bool;

    public function verifyWebhookSignature(string $rawPayload, ?string $signature): bool;

    public function parseWebhookEvent(array $payload): array;
}
