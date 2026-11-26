<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderBookingResource;
use App\Http\Resources\ProviderReserveRequestResource;
use App\Models\Booking;
use App\Models\Provider;
use App\Services\Provider\ProviderReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderReportController extends Controller
{
    public function __construct(
        private readonly ProviderReportService $reportService
    ) {}

    public function reserveRequests(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $filters = $request->only(['workspace_id', 'status', 'from_date', 'to_date']);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        $query = $this->reportService->reserveRequestsQuery($provider, $filters);
        $summary = [
            'total_requests' => (clone $query)->count(),
            'status_breakdown' => (clone $query)->reorder()->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
        ];

        $paginated = $query->paginate($perPage);

        return ProviderReserveRequestResource::collection($paginated)
            ->additional(['summary' => $summary])
            ->response();
    }

    public function reserveRequestsExport(Request $request): StreamedResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->reportService->streamReserveRequestsCsv(
            $provider,
            $request->only(['workspace_id', 'status', 'from_date', 'to_date'])
        );
    }

    public function instantBookings(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $filters = $request->only(['workspace_id', 'status', 'payment_status', 'from_date', 'to_date']);
        $perPage = max(1, min(100, (int) $request->integer('per_page', 20)));

        $query = $this->reportService->instantBookingsQuery($provider, $filters);

        $paidTotal = (float) (clone $query)->reorder()->where('payment_status', Booking::PAYMENT_PAID)->sum('total_amount');
        $summary = [
            'total_bookings' => (clone $query)->count(),
            'total_seats' => (int) (clone $query)->reorder()->sum('seat_count'),
            'paid_revenue' => round($paidTotal, 2),
            'status_breakdown' => (clone $query)->reorder()->selectRaw('status, COUNT(*) as count')->groupBy('status')->pluck('count', 'status'),
        ];

        $paginated = $query->paginate($perPage);

        return ProviderBookingResource::collection($paginated)
            ->additional(['summary' => $summary])
            ->response();
    }

    public function instantBookingsExport(Request $request): StreamedResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->reportService->streamInstantBookingsCsv(
            $provider,
            $request->only(['workspace_id', 'status', 'payment_status', 'from_date', 'to_date'])
        );
    }
}
