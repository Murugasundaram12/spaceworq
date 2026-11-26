<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CheckAvailabilityRequest;
use App\Http\Requests\Customer\CreateInstantBookingRequest;
use App\Http\Requests\Customer\QuoteBookingRequest;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Models\InventoryReservation;
use App\Models\Workspace;
use App\Services\AuditLogService;
use App\Services\Booking\AvailabilityService;
use App\Services\Booking\BookingService;
use App\Services\Booking\QuoteCalculationService;
use App\Services\Public\WorkspaceVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class BookingController extends Controller
{
    public function __construct(
        private readonly AvailabilityService $availabilityService,
        private readonly QuoteCalculationService $quoteService,
        private readonly BookingService $bookingService,
        private readonly WorkspaceVisibilityService $visibilityService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function availability(Workspace $workspace, CheckAvailabilityRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $availability = $this->availabilityService->checkAvailability(
            $workspace,
            (string) $validated['booking_date'],
            (int) $validated['seat_count'],
            $validated['slot'] ?? 'fullday',
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null
        );

        return response()->json(['data' => $availability]);
    }

    public function quote(Workspace $workspace, QuoteBookingRequest $request): JsonResponse
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            abort(404, 'Workspace not found.');
        }

        $validated = $request->validated();

        $quote = $this->quoteService->calculate(
            $workspace,
            (int) $validated['seat_count'],
            $validated['slot'] ?? 'fullday',
            $validated['start_time'] ?? null,
            $validated['end_time'] ?? null,
            $validated['coupon_code'] ?? null,
            $request->user('sanctum')
        );

        return response()->json([
            'data' => [
                'listing_config_id' => $quote['listing_config_id'],
                'slot' => $quote['slot'],
                'start_time' => $quote['start_time'],
                'end_time' => $quote['end_time'],
                'seat_count' => $quote['seat_count'],
                'unit_price' => $quote['unit_price'],
                'subtotal_amount' => $quote['subtotal_amount'],
                'coupon_code' => $quote['coupon_code'],
                'discount_amount' => $quote['discount_amount'],
                'taxable_amount' => $quote['taxable_amount'],
                'tax_rate' => $quote['tax_rate'],
                'tax_amount' => $quote['tax_amount'],
                'total_amount' => $quote['total_amount'],
                'currency' => $quote['currency'],
            ],
        ]);
    }

    public function store(Workspace $workspace, CreateInstantBookingRequest $request): JsonResponse
    {
        $data = $request->validated();
        if (empty($data['idempotency_key']) && $request->header('Idempotency-Key')) {
            $data['idempotency_key'] = (string) $request->header('Idempotency-Key');
        }

        $result = $this->bookingService->createInstantBooking(
            $workspace,
            $request->user(),
            $data
        );

        $status = $result['created'] ? 201 : 200;

        return (new BookingResource($result['booking']))
            ->additional(['meta' => ['created' => $result['created']]])
            ->response()
            ->setStatusCode($status);
    }

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));
        $buildingId = $request->input('buildingId', $request->input('workspace_id'));

        $query = Booking::query()
            ->where('customer_user_id', $request->user()->id)
            ->with(['workspace', 'items', 'paymentTransactions', 'cancellationRecord'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        if ($buildingId !== null && $buildingId !== '') {
            $query->where('workspace_id', (int) $buildingId);
        }

        return BookingResource::collection($query->paginate($perPage))->response();
    }

    public function show(Booking $booking): JsonResponse
    {
        Gate::authorize('viewCustomerBooking', $booking);

        return (new BookingResource(
            $booking->load(['workspace', 'items', 'paymentTransactions', 'cancellationRecord'])
        ))->response();
    }

    public function cancel(Booking $booking, Request $request): JsonResponse
    {
        Gate::authorize('manageAsCustomer', $booking);

        $validated = $request->validate([
            'reason' => ['nullable', 'string', 'max:500'],
        ]);

        $cancelled = $this->bookingService->cancelBooking(
            $booking,
            $request->user(),
            $validated['reason'] ?? null
        );

        return (new BookingResource($cancelled))->response();
    }

    public function complete(Booking $booking, Request $request): JsonResponse
    {
        Gate::authorize('manageAsCustomer', $booking);

        if ($booking->payment_status !== Booking::PAYMENT_PAID) {
            throw ValidationException::withMessages([
                'payment_status' => ['Booking can only be completed after payment is verified.'],
            ]);
        }

        if (in_array($booking->status, [Booking::STATUS_CANCELLED, Booking::STATUS_COMPLETED], true)) {
            throw ValidationException::withMessages([
                'status' => ["Booking cannot be completed from status [{$booking->status}]."],
            ]);
        }

        $booking->forceFill([
            'status' => Booking::STATUS_COMPLETED,
            'completed_at' => now(),
        ])->save();

        InventoryReservation::query()
            ->where('booking_id', $booking->id)
            ->update(['status' => InventoryReservation::STATUS_RELEASED]);

        $this->auditLogService->log('booking.completed', $request->user(), $booking, [
            'booking_reference' => $booking->booking_reference,
        ], $booking->provider_id, $request);

        return (new BookingResource($booking->fresh(['workspace', 'items', 'paymentTransactions'])))->response();
    }
}
