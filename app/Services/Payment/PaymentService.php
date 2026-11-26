<?php

namespace App\Services\Payment;

use App\Exceptions\PaymentGatewayNotConfiguredException;
use App\Models\Booking;
use App\Models\InventoryReservation;
use App\Models\PaymentTransaction;
use App\Models\User;
use App\Models\WebhookEvent;
use App\Models\Workspace;
use App\Notifications\PaymentStatusNotification;
use App\Services\AuditLogService;
use App\Services\Booking\AvailabilityService;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Symfony\Component\HttpKernel\Exception\HttpException;

class PaymentService
{
    public function __construct(
        private readonly PaymentGatewayInterface $gateway,
        private readonly AvailabilityService $availabilityService,
        private readonly AuditLogService $auditLogService
    ) {
    }

    public function initiatePayment(Booking $booking, User $customer, string $idempotencyKey): PaymentTransaction
    {
        if ($booking->customer_user_id !== $customer->id) {
            throw new HttpException(403, 'Forbidden: you do not own this booking.');
        }

        if ($booking->payment_status === Booking::PAYMENT_PAID) {
            throw ValidationException::withMessages(['booking' => ['Booking is already paid.']]);
        }

        if ($booking->status === Booking::STATUS_CANCELLED) {
            throw ValidationException::withMessages(['booking' => ['Cancelled booking cannot be paid.']]);
        }

        if (!$this->gateway->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException();
        }

        $existing = PaymentTransaction::query()
            ->where('idempotency_key', $idempotencyKey)
            ->where('booking_id', $booking->id)
            ->where('customer_user_id', $customer->id)
            ->first();

        if ($existing) {
            return $existing;
        }

        $holdExpired = DB::transaction(function () use ($booking, $customer): bool {
            /** @var Workspace $lockedWorkspace */
            Workspace::query()->whereKey($booking->workspace_id)->lockForUpdate()->firstOrFail();

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            $reservations = InventoryReservation::query()
                ->where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->get();

            if ($this->isHoldExpired($lockedBooking, $reservations)) {
                $this->expireHeldBooking($lockedBooking, $reservations, $customer, 'Booking hold expired prior to payment initiation.');

                return true;
            }

            return false;
        });

        if ($holdExpired) {
            throw ValidationException::withMessages([
                'hold_expires_at' => ['Booking hold has expired. Please create a new booking.'],
            ]);
        }

        $order = $this->gateway->createOrder($booking, $idempotencyKey);

        return DB::transaction(function () use ($booking, $customer, $idempotencyKey, $order): PaymentTransaction {
            $existing = PaymentTransaction::query()
                ->where('idempotency_key', $idempotencyKey)
                ->where('booking_id', $booking->id)
                ->where('customer_user_id', $customer->id)
                ->lockForUpdate()
                ->first();

            if ($existing) {
                return $existing;
            }

            $tx = PaymentTransaction::query()->create([
                'booking_id' => $booking->id,
                'customer_user_id' => $customer->id,
                'gateway' => $this->gateway->driverName(),
                'idempotency_key' => $idempotencyKey,
                'gateway_order_id' => $order['gateway_order_id'],
                'amount' => $booking->total_amount,
                'currency' => $booking->currency,
                'status' => PaymentTransaction::STATUS_CREATED,
                'gateway_payload' => $order['raw'] ?? null,
            ]);

            $booking->forceFill(['payment_status' => Booking::PAYMENT_PENDING])->save();

            $this->auditLogService->log('payment.initiated', $customer, $tx, [
                'booking_reference' => $booking->booking_reference,
                'gateway' => $tx->gateway,
                'amount' => $tx->amount,
            ], $booking->provider_id);

            return $tx;
        });
    }

    public function verifyPayment(Booking $booking, User $customer, array $payload): PaymentTransaction
    {
        if ($booking->customer_user_id !== $customer->id) {
            throw new HttpException(403, 'Forbidden: you do not own this booking.');
        }

        if (!$this->gateway->isConfigured()) {
            throw new PaymentGatewayNotConfiguredException();
        }

        if (!$this->gateway->verifyPaymentSignature($payload)) {
            throw ValidationException::withMessages([
                'signature' => ['Payment signature verification failed.'],
            ]);
        }

        $orderId = (string) ($payload['gateway_order_id'] ?? $payload['razorpay_order_id']);
        $paymentId = (string) ($payload['gateway_payment_id'] ?? $payload['razorpay_payment_id']);

        $lookupTx = PaymentTransaction::query()
            ->where('booking_id', $booking->id)
            ->where('gateway_order_id', $orderId)
            ->first();

        if (!$lookupTx) {
            throw ValidationException::withMessages([
                'gateway_order_id' => ['Matching payment order not found for this booking.'],
            ]);
        }

        $outcome = DB::transaction(function () use ($booking, $customer, $lookupTx, $paymentId): array {
            /** @var Workspace $lockedWorkspace */
            $lockedWorkspace = Workspace::query()->whereKey($booking->workspace_id)->lockForUpdate()->firstOrFail();

            /** @var Booking $lockedBooking */
            $lockedBooking = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            /** @var PaymentTransaction $lockedTx */
            $lockedTx = PaymentTransaction::query()->whereKey($lookupTx->id)->lockForUpdate()->firstOrFail();

            $reservations = InventoryReservation::query()
                ->where('booking_id', $lockedBooking->id)
                ->lockForUpdate()
                ->get();

            return $this->attemptConfirmPayment(
                $lockedWorkspace,
                $lockedBooking,
                $lockedTx,
                $reservations,
                $customer,
                $paymentId,
                false
            );
        });

        if (!$outcome['confirmed']) {
            throw ValidationException::withMessages([
                $outcome['error_field'] => [$outcome['error_message']],
            ]);
        }

        if (!$outcome['already_processed']) {
            $customer->notify(new PaymentStatusNotification($outcome['booking'], $outcome['transaction']));
        }

        return $outcome['transaction'];
    }

    public function handleWebhook(string $gatewayName, string $rawPayload, ?string $signature): array
    {
        if ($gatewayName !== $this->gateway->driverName()) {
            throw new HttpException(404, 'Unknown payment gateway.');
        }

        if (!$this->gateway->isConfigured() || !$this->gateway->verifyWebhookSignature($rawPayload, $signature)) {
            throw new HttpException(403, 'Invalid or unconfigured webhook signature.');
        }

        $decoded = json_decode($rawPayload, true);
        if (!is_array($decoded)) {
            throw ValidationException::withMessages(['payload' => ['Invalid JSON webhook payload.']]);
        }

        $parsed = $this->gateway->parseWebhookEvent($decoded);

        $existing = WebhookEvent::query()->where('event_id', $parsed['event_id'])->first();
        if ($existing) {
            return ['status' => 'already_processed', 'event_id' => $parsed['event_id']];
        }

        try {
            $result = DB::transaction(function () use ($gatewayName, $parsed, $decoded): array {
                $event = WebhookEvent::query()->create([
                    'gateway' => $gatewayName,
                    'event_id' => $parsed['event_id'],
                    'event_type' => $parsed['event_type'],
                    'signature_verified' => true,
                    'payload' => $decoded,
                    'status' => WebhookEvent::STATUS_RECEIVED,
                ]);

                $notificationTarget = null;
                $notificationBooking = null;
                $notificationTx = null;
                $resultStatus = 'processed';

                if ($parsed['gateway_order_id']) {
                    $lookupTx = PaymentTransaction::query()
                        ->where('gateway_order_id', $parsed['gateway_order_id'])
                        ->first();

                    if ($lookupTx) {
                        $bookingRef = Booking::query()->whereKey($lookupTx->booking_id)->first();

                        if ($bookingRef) {
                            /** @var Workspace $lockedWorkspace */
                            $lockedWorkspace = Workspace::query()->whereKey($bookingRef->workspace_id)->lockForUpdate()->firstOrFail();

                            /** @var Booking $lockedBooking */
                            $lockedBooking = Booking::query()->whereKey($bookingRef->id)->lockForUpdate()->firstOrFail();

                            /** @var PaymentTransaction $lockedTx */
                            $lockedTx = PaymentTransaction::query()->whereKey($lookupTx->id)->lockForUpdate()->firstOrFail();

                            $reservations = InventoryReservation::query()
                                ->where('booking_id', $lockedBooking->id)
                                ->lockForUpdate()
                                ->get();

                            if ($parsed['event_type'] === 'payment.captured') {
                                $outcome = $this->attemptConfirmPayment(
                                    $lockedWorkspace,
                                    $lockedBooking,
                                    $lockedTx,
                                    $reservations,
                                    $lockedBooking->customer,
                                    (string) ($parsed['gateway_payment_id'] ?? $lockedTx->gateway_payment_id ?? ''),
                                    true
                                );

                                if ($outcome['confirmed']) {
                                    if (!$outcome['already_processed']) {
                                        $notificationTarget = $lockedBooking->customer;
                                        $notificationBooking = $outcome['booking'];
                                        $notificationTx = $outcome['transaction'];
                                    }
                                } else {
                                    $event->forceFill([
                                        'status' => WebhookEvent::STATUS_FAILED,
                                        'error_message' => $outcome['error_message'],
                                        'processed_at' => now(),
                                    ])->save();

                                    return [
                                        'status' => $outcome['webhook_status'],
                                        'event_id' => $parsed['event_id'],
                                        'notification' => null,
                                    ];
                                }
                            } elseif ($parsed['event_type'] === 'payment.failed') {
                                $this->handlePaymentFailure($lockedBooking, $lockedTx, $reservations);
                            }
                        }
                    }
                }

                $event->forceFill([
                    'status' => WebhookEvent::STATUS_PROCESSED,
                    'processed_at' => now(),
                ])->save();

                return [
                    'status' => $resultStatus,
                    'event_id' => $parsed['event_id'],
                    'notification' => $notificationTarget ? [$notificationTarget, $notificationBooking, $notificationTx] : null,
                ];
            });
        } catch (UniqueConstraintViolationException) {
            return ['status' => 'already_processed', 'event_id' => $parsed['event_id']];
        }

        if (!empty($result['notification'])) {
            [$notifiable, $notifiedBooking, $notifiedTx] = $result['notification'];
            $notifiable->notify(new PaymentStatusNotification($notifiedBooking, $notifiedTx));
        }

        return [
            'status' => $result['status'],
            'event_id' => $result['event_id'],
        ];
    }

    private function attemptConfirmPayment(
        Workspace $lockedWorkspace,
        Booking $lockedBooking,
        PaymentTransaction $lockedTx,
        $reservations,
        ?User $actor,
        string $paymentId,
        bool $fromWebhook
    ): array {
        if ($lockedTx->status === PaymentTransaction::STATUS_CAPTURED && $lockedBooking->payment_status === Booking::PAYMENT_PAID) {
            return [
                'confirmed' => true,
                'already_processed' => true,
                'booking' => $lockedBooking,
                'transaction' => $lockedTx,
            ];
        }

        if ($lockedBooking->payment_status === Booking::PAYMENT_PAID) {
            $lockedTx->forceFill([
                'gateway_payment_id' => $paymentId ?: $lockedTx->gateway_payment_id,
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => 'Booking is already paid via another transaction.',
            ])->save();

            return [
                'confirmed' => false,
                'already_processed' => false,
                'error_field' => 'booking',
                'error_message' => 'Booking is already paid and confirmed.',
                'webhook_status' => 'already_paid',
            ];
        }

        if (in_array($lockedBooking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            InventoryReservation::query()
                ->where('booking_id', $lockedBooking->id)
                ->whereIn('status', [InventoryReservation::STATUS_HELD, InventoryReservation::STATUS_CONFIRMED])
                ->update(['status' => InventoryReservation::STATUS_RELEASED]);

            $lockedTx->forceFill([
                'gateway_payment_id' => $paymentId ?: $lockedTx->gateway_payment_id,
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => "Booking in status [{$lockedBooking->status}] cannot be confirmed.",
            ])->save();

            $this->auditLogService->log('payment.confirmation_rejected', $actor, $lockedTx, [
                'booking_reference' => $lockedBooking->booking_reference,
                'booking_status' => $lockedBooking->status,
                'from_webhook' => $fromWebhook,
            ], $lockedBooking->provider_id);

            return [
                'confirmed' => false,
                'already_processed' => false,
                'error_field' => 'status',
                'error_message' => "Booking in status [{$lockedBooking->status}] cannot be confirmed.",
                'webhook_status' => 'booking_not_confirmable',
            ];
        }

        if ($this->isHoldExpired($lockedBooking, $reservations)) {
            $reason = $fromWebhook
                ? 'Booking hold expired before webhook capture; manual reconciliation/refund required.'
                : 'Booking hold has expired and cannot be confirmed.';

            $this->expireHeldBooking($lockedBooking, $reservations, $actor, $reason, $lockedTx, $paymentId);

            return [
                'confirmed' => false,
                'already_processed' => false,
                'error_field' => 'hold_expires_at',
                'error_message' => $reason,
                'webhook_status' => 'hold_expired',
            ];
        }

        try {
            $availability = $this->availabilityService->checkAvailability(
                $lockedWorkspace,
                $lockedBooking->booking_date->toDateString(),
                $lockedBooking->seat_count,
                $lockedBooking->slot,
                $lockedBooking->start_time,
                $lockedBooking->end_time,
                $lockedBooking->id
            );
        } catch (ValidationException $e) {
            $availability = [
                'available' => false,
                'reason' => $e->getMessage(),
            ];
        }

        if (!($availability['available'] ?? false)) {
            $reason = $availability['reason'] ?? 'Insufficient capacity remaining to confirm booking.';
            $this->expireHeldBooking($lockedBooking, $reservations, $actor, $reason, $lockedTx, $paymentId, InventoryReservation::STATUS_RELEASED);

            return [
                'confirmed' => false,
                'already_processed' => false,
                'error_field' => 'seat_count',
                'error_message' => $reason,
                'webhook_status' => 'insufficient_capacity',
            ];
        }

        $lockedTx->forceFill([
            'gateway_payment_id' => $paymentId ?: $lockedTx->gateway_payment_id,
            'status' => PaymentTransaction::STATUS_CAPTURED,
            'verified_at' => now(),
            'failure_reason' => null,
        ])->save();

        $lockedBooking->forceFill([
            'payment_status' => Booking::PAYMENT_PAID,
            'status' => Booking::STATUS_UPCOMING,
            'hold_expires_at' => null,
        ])->save();

        InventoryReservation::query()
            ->where('booking_id', $lockedBooking->id)
            ->update([
                'status' => InventoryReservation::STATUS_CONFIRMED,
                'expires_at' => null,
            ]);

        $this->auditLogService->log(
            $fromWebhook ? 'payment.webhook_captured' : 'payment.verified',
            $actor,
            $lockedTx,
            [
                'booking_reference' => $lockedBooking->booking_reference,
                'gateway_payment_id' => $lockedTx->gateway_payment_id,
                'from_webhook' => $fromWebhook,
            ],
            $lockedBooking->provider_id
        );

        return [
            'confirmed' => true,
            'already_processed' => false,
            'booking' => $lockedBooking->fresh(),
            'transaction' => $lockedTx->fresh(),
        ];
    }

    private function handlePaymentFailure(Booking $lockedBooking, PaymentTransaction $lockedTx, $reservations): void
    {
        if ($lockedTx->status === PaymentTransaction::STATUS_CAPTURED || $lockedBooking->payment_status === Booking::PAYMENT_PAID) {
            return;
        }

        $lockedTx->forceFill([
            'status' => PaymentTransaction::STATUS_FAILED,
            'failure_reason' => 'Payment failed at gateway webhook.',
        ])->save();

        if ($this->isHoldExpired($lockedBooking, $reservations)) {
            $this->expireHeldBooking(
                $lockedBooking,
                $reservations,
                $lockedBooking->customer,
                'Booking hold expired after payment failure.'
            );

            return;
        }

        $lockedBooking->forceFill(['payment_status' => Booking::PAYMENT_FAILED])->save();
    }

    private function isHoldExpired(Booking $booking, $reservations): bool
    {
        if ($booking->hold_expires_at !== null && $booking->hold_expires_at->isPast()) {
            return true;
        }

        foreach ($reservations as $reservation) {
            if (
                $reservation->status === InventoryReservation::STATUS_HELD
                && $reservation->expires_at !== null
                && $reservation->expires_at->isPast()
            ) {
                return true;
            }
        }

        return false;
    }

    private function expireHeldBooking(
        Booking $lockedBooking,
        $reservations,
        ?User $actor,
        string $reason,
        ?PaymentTransaction $lockedTx = null,
        ?string $paymentId = null,
        string $reservationTargetStatus = InventoryReservation::STATUS_EXPIRED
    ): void {
        if ($lockedBooking->status === Booking::STATUS_PENDING_PAYMENT) {
            $lockedBooking->forceFill([
                'status' => Booking::STATUS_CANCELLED,
                'payment_status' => $lockedBooking->payment_status === Booking::PAYMENT_PENDING
                    ? Booking::PAYMENT_FAILED
                    : $lockedBooking->payment_status,
                'cancelled_at' => $lockedBooking->cancelled_at ?? now(),
                'remarks' => $reason,
            ])->save();
        }

        InventoryReservation::query()
            ->where('booking_id', $lockedBooking->id)
            ->where('status', InventoryReservation::STATUS_HELD)
            ->update(['status' => $reservationTargetStatus]);

        if ($lockedTx !== null && $lockedTx->status !== PaymentTransaction::STATUS_CAPTURED) {
            $lockedTx->forceFill([
                'gateway_payment_id' => ($paymentId ?: $lockedTx->gateway_payment_id),
                'status' => PaymentTransaction::STATUS_FAILED,
                'failure_reason' => $reason,
            ])->save();
        }

        $this->auditLogService->log('payment.hold_expired_or_rejected', $actor, $lockedBooking, [
            'booking_reference' => $lockedBooking->booking_reference,
            'reason' => $reason,
            'gateway_payment_id' => $paymentId ?: null,
        ], $lockedBooking->provider_id);
    }
}
