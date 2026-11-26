<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderBookingResource;
use App\Models\Booking;
use App\Models\InventoryReservation;
use App\Models\Provider;
use App\Models\ProviderUser;
use App\Notifications\BookingStatusNotification;
use App\Services\AuditLogService;
use App\Services\Booking\BookingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProviderInstantBookingController extends Controller
{
    public function __construct(
        private readonly BookingService $bookingService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));

        $query = Booking::query()
            ->where('provider_id', $provider->id)
            ->with(['workspace', 'assignedStaff.user'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('payment_status')) {
            $query->where('payment_status', (string) $request->input('payment_status'));
        }
        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(booking_reference) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(contact_name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(contact_email) LIKE ?', [$term]);
            });
        }

        return ProviderBookingResource::collection($query->paginate($perPage))->response();
    }

    public function updateStatus(Booking $booking, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($booking->provider_id !== $provider->id) {
            abort(403, 'Forbidden: booking belongs to another provider.');
        }

        Gate::authorize('manageAsProvider', $booking);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', Booking::PROVIDER_STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'assigned_provider_user_id' => ['nullable', 'integer', 'exists:provider_users,id'],
        ]);

        if (! empty($validated['assigned_provider_user_id'])) {
            $staff = ProviderUser::query()
                ->where('id', $validated['assigned_provider_user_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $staff) {
                abort(403, 'Forbidden: assigned staff belongs to another provider.');
            }
        }

        $targetStatus = $validated['status'];

        if (
            in_array($targetStatus, [Booking::STATUS_ACTIVE, Booking::STATUS_IN_PROGRESS, Booking::STATUS_COMPLETED], true)
            && $booking->payment_status !== Booking::PAYMENT_PAID
        ) {
            throw ValidationException::withMessages([
                'payment_status' => ['Booking must be paid before transitioning to Active, In Progress, or Completed.'],
            ]);
        }

        if ($targetStatus === Booking::STATUS_CANCELLED) {
            $updated = $this->bookingService->cancelBooking(
                $booking,
                $request->user(),
                $validated['remarks'] ?? null
            );

            return (new ProviderBookingResource($updated->load(['workspace', 'assignedStaff.user'])))->response();
        }

        $booking->forceFill([
            'status' => $targetStatus,
            'remarks' => $validated['remarks'] ?? $booking->remarks,
            'assigned_provider_user_id' => $validated['assigned_provider_user_id'] ?? $booking->assigned_provider_user_id,
            'completed_at' => $targetStatus === Booking::STATUS_COMPLETED ? now() : $booking->completed_at,
        ])->save();

        if ($targetStatus === Booking::STATUS_COMPLETED) {
            InventoryReservation::query()
                ->where('booking_id', $booking->id)
                ->update(['status' => InventoryReservation::STATUS_RELEASED]);
        }

        $this->auditLogService->log('provider.booking_status_updated', $request->user(), $booking, [
            'status' => $targetStatus,
        ], $provider->id, $request);

        if ($booking->customer) {
            $booking->customer->notify(new BookingStatusNotification($booking));
        }

        return (new ProviderBookingResource($booking->fresh(['workspace', 'assignedStaff.user'])))->response();
    }

    public function assign(Booking $booking, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($booking->provider_id !== $provider->id) {
            abort(403, 'Forbidden: booking belongs to another provider.');
        }

        Gate::authorize('manageAsProvider', $booking);

        $validated = $request->validate([
            'assigned_provider_user_id' => ['nullable', 'integer', 'exists:provider_users,id'],
        ]);

        if (! empty($validated['assigned_provider_user_id'])) {
            $staff = ProviderUser::query()
                ->where('id', $validated['assigned_provider_user_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $staff) {
                abort(403, 'Forbidden: assigned staff belongs to another provider.');
            }
        }

        $booking->forceFill([
            'assigned_provider_user_id' => $validated['assigned_provider_user_id'] ?? null,
        ])->save();

        $this->auditLogService->log('provider.booking_assigned', $request->user(), $booking, [
            'assigned_provider_user_id' => $booking->assigned_provider_user_id,
        ], $provider->id, $request);

        return (new ProviderBookingResource($booking->fresh(['workspace', 'assignedStaff.user'])))->response();
    }

    public function resetPast(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $counts = DB::transaction(function () use ($provider): array {
            $today = now()->toDateString();
            $pastBookings = Booking::query()
                ->where('provider_id', $provider->id)
                ->whereDate('booking_date', '<', $today)
                ->whereIn('status', [
                    Booking::STATUS_PENDING_PAYMENT,
                    Booking::STATUS_UPCOMING,
                    Booking::STATUS_ACTIVE,
                    Booking::STATUS_IN_PROGRESS,
                    Booking::STATUS_REVIEW,
                ])
                ->lockForUpdate()
                ->get();

            $completedCount = 0;
            $cancelledCount = 0;

            foreach ($pastBookings as $booking) {
                if ($booking->payment_status === Booking::PAYMENT_PAID) {
                    $booking->forceFill([
                        'status' => Booking::STATUS_COMPLETED,
                        'completed_at' => now(),
                    ])->save();
                    $completedCount++;
                } else {
                    $booking->forceFill([
                        'status' => Booking::STATUS_CANCELLED,
                        'cancelled_at' => now(),
                        'remarks' => 'Reset past unpaid booking.',
                    ])->save();
                    $cancelledCount++;
                }

                InventoryReservation::query()
                    ->where('booking_id', $booking->id)
                    ->update(['status' => InventoryReservation::STATUS_RELEASED]);
            }

            return [
                'completed_count' => $completedCount,
                'cancelled_count' => $cancelledCount,
                'total_reset' => $completedCount + $cancelledCount,
            ];
        });

        $this->auditLogService->log('provider.bookings_reset_past', $request->user(), $provider, $counts, $provider->id, $request);

        return response()->json(['data' => $counts]);
    }
}
