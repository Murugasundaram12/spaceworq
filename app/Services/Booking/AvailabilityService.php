<?php

namespace App\Services\Booking;

use App\Models\InventoryReservation;
use App\Models\SystemSetting;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceListingConfig;
use App\Services\Public\WorkspaceVisibilityService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AvailabilityService
{
    public const SLOT_TIMES = [
        'morning' => ['09:00:00', '13:00:00'],
        'afternoon' => ['13:00:00', '17:00:00'],
        'fullday' => ['09:00:00', '20:00:00'],
    ];

    public function __construct(
        private readonly WorkspaceVisibilityService $visibilityService
    ) {}

    public function checkAvailability(
        Workspace $workspace,
        string $bookingDate,
        int $seatCount,
        ?string $slot = 'fullday',
        ?string $startTime = null,
        ?string $endTime = null,
        ?int $excludeBookingId = null
    ): array {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            throw ValidationException::withMessages([
                'workspace_id' => ['Workspace is not available for booking.'],
            ]);
        }

        $workspace->loadMissing(['category', 'listingConfigs', 'workingHours']);

        $allowNonDayPass = (bool) (SystemSetting::getValue('allow_non_day_pass_instant_book')
            ?? config('spaceworq.booking.allow_non_day_pass_instant_book', false));

        $instantCategories = [
            WorkspaceCategory::CODE_DAY_PASS,
            WorkspaceCategory::CODE_MEETINGS_EVENTS,
        ];

        $categoryCode = $workspace->category?->code;
        if (! in_array($categoryCode, $instantCategories, true) && ! $allowNonDayPass) {
            throw ValidationException::withMessages([
                'workspace_id' => ['This workspace requires a Reserve Request rather than instant booking.'],
            ]);
        }

        if (! $workspace->is_instant_bookable) {
            throw ValidationException::withMessages([
                'workspace_id' => ['Instant booking is not enabled for this workspace.'],
            ]);
        }

        $date = Carbon::parse($bookingDate)->startOfDay();
        if ($date->lt(now()->startOfDay())) {
            throw ValidationException::withMessages([
                'booking_date' => ['Booking date cannot be in the past.'],
            ]);
        }

        [$resolvedSlot, $resolvedStart, $resolvedEnd] = $this->resolveTimes($slot, $startTime, $endTime);

        $dayOfWeek = $date->dayOfWeekIso - 1; // 0 = Monday ... 6 = Sunday
        $workingHour = $workspace->workingHours->firstWhere('day_of_week', $dayOfWeek);
        if ($workingHour && $workingHour->is_closed) {
            return [
                'available' => false,
                'reason' => 'Workspace is closed on the selected date.',
                'remaining_seats' => 0,
                'capacity' => 0,
                'slot' => $resolvedSlot,
                'start_time' => $resolvedStart,
                'end_time' => $resolvedEnd,
            ];
        }

        $config = $this->resolveActiveConfig($workspace);
        $maxPlatformSeats = SystemSetting::getValue('max_instant_booking_seats')
            ?? config('spaceworq.booking.max_instant_booking_seats');

        $capacity = (int) $workspace->available_seats;
        if ($config && $config->max_seats !== null) {
            $capacity = min($capacity, (int) $config->max_seats);
        }

        if ($maxPlatformSeats !== null && $seatCount > (int) $maxPlatformSeats) {
            throw ValidationException::withMessages([
                'seat_count' => ["Requested seats exceed maximum instant booking limit ({$maxPlatformSeats})."],
            ]);
        }

        if ($config && $seatCount < $config->min_seats) {
            throw ValidationException::withMessages([
                'seat_count' => ["Minimum seats required is {$config->min_seats}."],
            ]);
        }

        $reservedSeats = (int) InventoryReservation::query()
            ->where('workspace_id', $workspace->id)
            ->when($excludeBookingId !== null, fn ($q) => $q->where('booking_id', '!=', $excludeBookingId))
            ->whereDate('reservation_date', $date->toDateString())
            ->where(function ($q): void {
                $q->where('status', InventoryReservation::STATUS_CONFIRMED)
                    ->orWhere(function ($hq): void {
                        $hq->where('status', InventoryReservation::STATUS_HELD)
                            ->where(function ($eq): void {
                                $eq->whereNull('expires_at')->orWhere('expires_at', '>', now());
                            });
                    });
            })
            ->where('start_time', '<', $resolvedEnd)
            ->where('end_time', '>', $resolvedStart)
            ->when(DB::transactionLevel() > 0, fn ($q) => $q->lockForUpdate())
            ->sum('seats_reserved');

        $remaining = max(0, $capacity - $reservedSeats);

        return [
            'available' => $remaining >= $seatCount && $seatCount > 0,
            'reason' => $remaining >= $seatCount ? null : 'Insufficient seats available for the selected slot.',
            'remaining_seats' => $remaining,
            'reserved_seats' => $reservedSeats,
            'capacity' => $capacity,
            'slot' => $resolvedSlot,
            'start_time' => $resolvedStart,
            'end_time' => $resolvedEnd,
        ];
    }

    public function resolveTimes(?string $slot, ?string $startTime, ?string $endTime): array
    {
        if ($startTime && $endTime) {
            $start = Carbon::createFromFormat('H:i', substr($startTime, 0, 5));
            $end = Carbon::createFromFormat('H:i', substr($endTime, 0, 5));

            if ($end->lte($start)) {
                throw ValidationException::withMessages([
                    'end_time' => ['End time must be after start time.'],
                ]);
            }

            return [$slot ?: 'custom', $start->format('H:i:s'), $end->format('H:i:s')];
        }

        $key = strtolower((string) ($slot ?: 'fullday'));
        if (! isset(self::SLOT_TIMES[$key])) {
            throw ValidationException::withMessages([
                'slot' => ['Invalid booking slot selected.'],
            ]);
        }

        return [$key, self::SLOT_TIMES[$key][0], self::SLOT_TIMES[$key][1]];
    }

    public function resolveActiveConfig(Workspace $workspace): ?WorkspaceListingConfig
    {
        $configs = $workspace->listingConfigs->where('is_active', true);
        return $configs->firstWhere('subcategory_id', $workspace->subcategory_id) ?? $configs->first();
    }
}
