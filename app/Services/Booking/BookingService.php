<?php

namespace App\Services\Booking;

use App\Models\Booking;
use App\Models\BookingItem;
use App\Models\CancellationRecord;
use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\InventoryReservation;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\BookingStatusNotification;
use App\Services\AuditLogService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class BookingService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly QuoteCalculationService $quoteService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function createInstantBooking(Workspace $workspace, User $customer, array $data): array
    {
        $idempotencyKey = $data['idempotency_key'] ?? null;

        if ($idempotencyKey !== null && trim((string) $idempotencyKey) !== '') {
            $existing = Booking::query()
                ->with(['workspace', 'items', 'inventoryReservations'])
                ->where('idempotency_key', trim((string) $idempotencyKey))
                ->where('customer_user_id', $customer->id)
                ->first();

            if ($existing) {
                return ['booking' => $existing, 'created' => false];
            }
        }

        $booking = DB::transaction(function () use ($workspace, $customer, $data, $idempotencyKey): Booking {
            /** @var Workspace $lockedWorkspace */
            $lockedWorkspace = Workspace::query()->whereKey($workspace->id)->lockForUpdate()->firstOrFail();

            if ($idempotencyKey !== null && trim((string) $idempotencyKey) !== '') {
                $existing = Booking::query()
                    ->where('idempotency_key', trim((string) $idempotencyKey))
                    ->where('customer_user_id', $customer->id)
                    ->first();
                if ($existing) {
                    return $existing;
                }
            }

            $seatCount = (int) $data['seat_count'];
            $bookingDate = (string) $data['booking_date'];
            $slot = $data['slot'] ?? 'fullday';
            $startTime = $data['start_time'] ?? null;
            $endTime = $data['end_time'] ?? null;

            $availability = $this->availabilityService->checkAvailability(
                $lockedWorkspace,
                $bookingDate,
                $seatCount,
                $slot,
                $startTime,
                $endTime
            );

            if (! $availability['available']) {
                throw ValidationException::withMessages([
                    'seat_count' => [$availability['reason'] ?? 'Selected seats are not available.'],
                ]);
            }

            $lockedCoupon = null;
            if (! empty($data['coupon_code']) && trim((string) $data['coupon_code']) !== '') {
                $lockedCoupon = Coupon::query()
                    ->where('code', strtoupper(trim((string) $data['coupon_code'])))
                    ->lockForUpdate()
                    ->first();
            }

            $quote = $this->quoteService->calculate(
                $lockedWorkspace,
                $seatCount,
                $availability['slot'],
                $availability['start_time'],
                $availability['end_time'],
                $data['coupon_code'] ?? null,
                $customer
            );

            $holdMinutes = SystemSetting::getValue('booking_hold_minutes')
                ?? config('spaceworq.booking.hold_minutes');

            $holdExpiresAt = $holdMinutes !== null ? now()->addMinutes((int) $holdMinutes) : null;

            $booking = Booking::query()->create([
                'booking_reference' => 'SWQ-' . strtoupper(Str::random(10)),
                'idempotency_key' => $idempotencyKey ? trim((string) $idempotencyKey) : null,
                'workspace_id' => $lockedWorkspace->id,
                'provider_id' => $lockedWorkspace->provider_id,
                'customer_user_id' => $customer->id,
                'booking_type' => 'INSTANT_BOOKING',
                'booking_date' => $bookingDate,
                'slot' => $quote['slot'],
                'start_time' => $quote['start_time'],
                'end_time' => $quote['end_time'],
                'seat_count' => $seatCount,
                'unit_price' => $quote['unit_price'],
                'subtotal_amount' => $quote['subtotal_amount'],
                'coupon_id' => $quote['coupon']?->id,
                'coupon_code' => $quote['coupon_code'],
                'discount_amount' => $quote['discount_amount'],
                'taxable_amount' => $quote['taxable_amount'],
                'tax_rate' => $quote['tax_rate'],
                'tax_amount' => $quote['tax_amount'],
                'total_amount' => $quote['total_amount'],
                'currency' => $quote['currency'],
                'status' => Booking::STATUS_PENDING_PAYMENT,
                'payment_status' => Booking::PAYMENT_UNPAID,
                'hold_expires_at' => $holdExpiresAt,
                'contact_name' => $data['contact_name'] ?? $customer->name,
                'contact_email' => $data['contact_email'] ?? $customer->email,
                'contact_phone' => $data['contact_phone'] ?? ($customer->mobile ?? ''),
                'checkin_token' => hash('sha256', Str::uuid()->toString()),
            ]);

            BookingItem::query()->create([
                'booking_id' => $booking->id,
                'workspace_listing_config_id' => $quote['listing_config_id'],
                'item_label' => "{$lockedWorkspace->name} ({$quote['slot']})",
                'quantity' => $seatCount,
                'unit_price' => $quote['unit_price'],
                'line_total' => $quote['subtotal_amount'],
            ]);

            InventoryReservation::query()->create([
                'workspace_id' => $lockedWorkspace->id,
                'booking_id' => $booking->id,
                'reservation_date' => $bookingDate,
                'start_time' => $quote['start_time'],
                'end_time' => $quote['end_time'],
                'seats_reserved' => $seatCount,
                'status' => InventoryReservation::STATUS_HELD,
                'expires_at' => $holdExpiresAt,
            ]);

            if ($quote['coupon']) {
                $lockedCoupon = $lockedCoupon ?? Coupon::query()->whereKey($quote['coupon']->id)->lockForUpdate()->firstOrFail();
                if ($lockedCoupon->usage_limit !== null && $lockedCoupon->used_count >= $lockedCoupon->usage_limit) {
                    throw ValidationException::withMessages(['coupon_code' => ['Coupon usage limit has been reached.']]);
                }
                if ($lockedCoupon->per_user_limit !== null) {
                    $userUsageCount = CouponUsage::query()
                        ->where('coupon_id', $lockedCoupon->id)
                        ->where('user_id', $customer->id)
                        ->lockForUpdate()
                        ->count();
                    if ($userUsageCount >= $lockedCoupon->per_user_limit) {
                        throw ValidationException::withMessages(['coupon_code' => ['You have exceeded the usage limit for this coupon.']]);
                    }
                }
                $lockedCoupon->increment('used_count');

                CouponUsage::query()->create([
                    'coupon_id' => $lockedCoupon->id,
                    'user_id' => $customer->id,
                    'booking_id' => $booking->id,
                    'discount_amount' => $quote['discount_amount'],
                ]);
            }

            $this->auditLogService->log('booking.created', $customer, $booking, [
                'booking_reference' => $booking->booking_reference,
                'seat_count' => $seatCount,
                'total_amount' => $booking->total_amount,
            ], $lockedWorkspace->provider_id);

            return $booking;
        });

        $customer->notify(new BookingStatusNotification($booking));

        return [
            'booking' => $booking->load(['workspace', 'items', 'inventoryReservations']),
            'created' => true,
        ];
    }

    public function cancelBooking(Booking $booking, User $actor, ?string $reason = null): Booking
    {
        return DB::transaction(function () use ($booking, $actor, $reason): Booking {
            /** @var Booking $locked */
            $locked = Booking::query()->whereKey($booking->id)->lockForUpdate()->firstOrFail();

            if (in_array($locked->status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
                throw ValidationException::withMessages([
                    'status' => ["Booking cannot be cancelled from status [{$locked->status}]."],
                ]);
            }

            $cutoffHours = SystemSetting::getValue('cancellation_cutoff_hours')
                ?? config('spaceworq.booking.cancellation_cutoff_hours');

            $refundable = 0.00;
            if ($locked->payment_status === Booking::PAYMENT_PAID && $cutoffHours !== null) {
                $startDt = $locked->booking_date->copy()->setTimeFromTimeString($locked->start_time);
                if (now()->diffInHours($startDt, false) >= (int) $cutoffHours) {
                    $refundable = (float) $locked->total_amount;
                }
            }

            $locked->forceFill([
                'status' => Booking::STATUS_CANCELLED,
                'cancelled_at' => now(),
                'remarks' => $reason ?? $locked->remarks,
            ])->save();

            InventoryReservation::query()
                ->where('booking_id', $locked->id)
                ->update(['status' => InventoryReservation::STATUS_RELEASED]);

            CancellationRecord::query()->create([
                'booking_id' => $locked->id,
                'cancelled_by_user_id' => $actor->id,
                'reason' => $reason,
                'policy_snapshot' => [
                    'cancellation_cutoff_hours' => $cutoffHours,
                    'payment_status_at_cancel' => $locked->payment_status,
                ],
                'refundable_amount' => $refundable,
                'cancelled_at' => now(),
            ]);

            $this->auditLogService->log('booking.cancelled', $actor, $locked, [
                'booking_reference' => $locked->booking_reference,
                'refundable_amount' => $refundable,
            ], $locked->provider_id);

            return $locked->fresh(['workspace', 'cancellationRecord']);
        });
    }
}
