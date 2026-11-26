<?php

namespace App\Services\Booking;

use App\Models\Coupon;
use App\Models\CouponUsage;
use App\Models\SystemSetting;
use App\Models\User;
use App\Models\Workspace;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class QuoteCalculationService
{
    public function __construct(
        private readonly AvailabilityService $availabilityService
    ) {}

    public function calculate(
        Workspace $workspace,
        int $seatCount,
        string $slot = 'fullday',
        ?string $startTime = null,
        ?string $endTime = null,
        ?string $couponCode = null,
        ?User $customer = null
    ): array {
        $workspace->loadMissing(['category', 'listingConfigs']);
        $config = $this->availabilityService->resolveActiveConfig($workspace);

        if (! $config) {
            throw ValidationException::withMessages([
                'workspace_id' => ['Pricing configuration is not available for this workspace.'],
            ]);
        }

        [$resolvedSlot, $resolvedStart, $resolvedEnd] = $this->availabilityService->resolveTimes($slot, $startTime, $endTime);
        $unitPrice = $this->resolveUnitPrice($workspace, $config, $resolvedSlot, $resolvedStart, $resolvedEnd);

        $subtotal = round($unitPrice * $seatCount, 2);
        $discount = 0.00;
        $coupon = null;

        if ($couponCode !== null && trim($couponCode) !== '') {
            $coupon = $this->validateCoupon(strtoupper(trim($couponCode)), $workspace, $subtotal, $customer);
            $discount = $this->computeCouponDiscount($coupon, $subtotal);
        }

        $taxableAmount = round(max(0, $subtotal - $discount), 2);
        $taxRate = $this->resolveTaxRate($workspace);
        $taxAmount = $taxRate > 0 ? round($taxableAmount * ($taxRate / 100), 2) : 0.00;
        $totalAmount = round($taxableAmount + $taxAmount, 2);

        return [
            'listing_config_id' => $config->id,
            'slot' => $resolvedSlot,
            'start_time' => $resolvedStart,
            'end_time' => $resolvedEnd,
            'seat_count' => $seatCount,
            'unit_price' => $unitPrice,
            'subtotal_amount' => $subtotal,
            'coupon' => $coupon,
            'coupon_code' => $coupon?->code,
            'discount_amount' => $discount,
            'taxable_amount' => $taxableAmount,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'total_amount' => $totalAmount,
            'currency' => 'INR',
        ];
    }

    private function resolveUnitPrice(
        Workspace $workspace,
        $config,
        string $slot,
        string $startTime,
        string $endTime
    ): float {
        if ($slot === 'custom' && $config->price_per_hour !== null) {
            $hours = max(1, (int) ceil(Carbon::createFromFormat('H:i:s', $startTime)->diffInMinutes(Carbon::createFromFormat('H:i:s', $endTime)) / 60));
            return round((float) $config->price_per_hour * $hours, 2);
        }

        if (in_array($slot, ['morning', 'afternoon'], true)) {
            if ($config->price_per_half_day !== null) {
                return round((float) $config->price_per_half_day, 2);
            }

            $multiplier = SystemSetting::getValue('half_day_multiplier')
                ?? config('spaceworq.booking.half_day_multiplier');

            if ($multiplier !== null && $config->price_per_day !== null) {
                return round((float) $config->price_per_day * (float) $multiplier, 2);
            }

            throw ValidationException::withMessages([
                'slot' => ['Half-day booking requires an explicit half-day rate or approved platform multiplier.'],
            ]);
        }

        if ($config->price_per_day !== null) {
            return round((float) $config->price_per_day, 2);
        }

        if ($config->price_per_hour !== null) {
            $hours = max(1, (int) ceil(Carbon::createFromFormat('H:i:s', $startTime)->diffInMinutes(Carbon::createFromFormat('H:i:s', $endTime)) / 60));
            return round((float) $config->price_per_hour * $hours, 2);
        }

        throw ValidationException::withMessages([
            'workspace_id' => ['No applicable daily or hourly rate is configured for this workspace.'],
        ]);
    }

    private function resolveTaxRate(Workspace $workspace): float
    {
        if ($workspace->gst_enabled && $workspace->tax_rate !== null) {
            return round((float) $workspace->tax_rate, 2);
        }

        $platformDefault = SystemSetting::getValue('default_tax_rate')
            ?? config('spaceworq.booking.default_tax_rate');

        if ($platformDefault !== null) {
            return round((float) $platformDefault, 2);
        }

        return 0.00;
    }

    private function validateCoupon(string $code, Workspace $workspace, float $subtotal, ?User $customer): Coupon
    {
        $coupon = Coupon::query()->where('code', $code)->first();

        if (! $coupon || ! $coupon->is_active) {
            throw ValidationException::withMessages(['coupon_code' => ['Invalid or inactive coupon code.']]);
        }

        if ($coupon->starts_at && $coupon->starts_at->isFuture()) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon is not yet active.']]);
        }

        if ($coupon->expires_at && $coupon->expires_at->isPast()) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon has expired.']]);
        }

        if ($coupon->usage_limit !== null && $coupon->used_count >= $coupon->usage_limit) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon usage limit has been reached.']]);
        }

        if ($coupon->min_order_amount !== null && $subtotal < (float) $coupon->min_order_amount) {
            throw ValidationException::withMessages(['coupon_code' => ['Order subtotal does not meet the minimum amount for this coupon.']]);
        }

        if ($coupon->applicable_category_code !== null && $workspace->category?->code !== $coupon->applicable_category_code) {
            throw ValidationException::withMessages(['coupon_code' => ['Coupon does not apply to this workspace category.']]);
        }

        if ($customer && $coupon->per_user_limit !== null) {
            $userCount = CouponUsage::query()
                ->where('coupon_id', $coupon->id)
                ->where('user_id', $customer->id)
                ->when(DB::transactionLevel() > 0, fn ($q) => $q->lockForUpdate())
                ->count();
            if ($userCount >= $coupon->per_user_limit) {
                throw ValidationException::withMessages(['coupon_code' => ['You have exceeded the usage limit for this coupon.']]);
            }
        }

        return $coupon;
    }

    private function computeCouponDiscount(Coupon $coupon, float $subtotal): float
    {
        $raw = $coupon->discount_type === Coupon::TYPE_PERCENTAGE
            ? round($subtotal * ((float) $coupon->discount_value / 100), 2)
            : round((float) $coupon->discount_value, 2);

        if ($coupon->max_discount_amount !== null) {
            $raw = min($raw, (float) $coupon->max_discount_amount);
        }

        return round(min($subtotal, max(0, $raw)), 2);
    }
}
