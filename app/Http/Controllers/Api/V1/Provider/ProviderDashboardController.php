<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderBookingResource;
use App\Http\Resources\ProviderReserveRequestResource;
use App\Models\Booking;
use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\ReserveRequest;
use App\Models\Workspace;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderDashboardController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $workspaceId = $request->input('workspace_id');

        $workspaceBase = Workspace::query()->where('provider_id', $provider->id);
        $bookingBase = Booking::query()->where('provider_id', $provider->id);
        $reserveBase = ReserveRequest::query()->where('provider_id', $provider->id);

        if ($workspaceId !== null && $workspaceId !== '') {
            $bookingBase->where('workspace_id', (int) $workspaceId);
            $reserveBase->where('workspace_id', (int) $workspaceId);
        }

        $totalWorkspaces = (clone $workspaceBase)->count();
        $activeWorkspaces = (clone $workspaceBase)->where('status', Workspace::STATUS_ACTIVE)->count();
        $inReviewWorkspaces = (clone $workspaceBase)->where('status', Workspace::STATUS_IN_REVIEW)->count();
        $totalSeats = (int) (clone $workspaceBase)->sum('total_seats');
        $availableSeats = (int) (clone $workspaceBase)->sum('available_seats');

        $bookingCounts = (clone $bookingBase)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $reserveCounts = (clone $reserveBase)
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status');

        $paidRevenue = (float) (clone $bookingBase)
            ->where('payment_status', Booking::PAYMENT_PAID)
            ->sum('total_amount');

        $upcomingToday = (clone $bookingBase)
            ->whereDate('booking_date', now()->toDateString())
            ->whereIn('status', [Booking::STATUS_UPCOMING, Booking::STATUS_ACTIVE])
            ->count();

        $staffCount = ProviderUser::query()
            ->where('provider_id', $provider->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->count();

        $recentBookings = (clone $bookingBase)
            ->with(['workspace', 'assignedStaff.user'])
            ->latest('id')
            ->limit(5)
            ->get();

        $recentReserves = (clone $reserveBase)
            ->with(['workspace', 'customer', 'assignedStaff.user'])
            ->latest('id')
            ->limit(5)
            ->get();

        return response()->json([
            'data' => [
                'provider' => [
                    'id' => $provider->id,
                    'company_name' => $provider->company_name,
                    'status' => $provider->status,
                    'kyc_status' => $provider->kyc_status,
                ],
                'metrics' => [
                    'total_workspaces' => $totalWorkspaces,
                    'active_workspaces' => $activeWorkspaces,
                    'in_review_workspaces' => $inReviewWorkspaces,
                    'total_seats' => $totalSeats,
                    'available_seats' => $availableSeats,
                    'staff_count' => $staffCount,
                    'paid_revenue' => round($paidRevenue, 2),
                    'upcoming_today_bookings' => $upcomingToday,
                    'instant_bookings_by_status' => $bookingCounts,
                    'reserve_requests_by_status' => $reserveCounts,
                ],
                'recent_instant_bookings' => ProviderBookingResource::collection($recentBookings),
                'recent_reserve_requests' => ProviderReserveRequestResource::collection($recentReserves),
            ],
        ]);
    }
}
