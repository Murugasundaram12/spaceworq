<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\CreateReserveRequest;
use App\Http\Resources\ReserveRequestResource;
use App\Models\ReserveRequest;
use App\Models\Workspace;
use App\Services\Booking\ReserveRequestService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReserveRequestController extends Controller
{
    public function __construct(
        private readonly ReserveRequestService $reserveRequestService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));
        $buildingId = $request->input('buildingId', $request->input('workspace_id'));

        $query = ReserveRequest::query()
            ->where('customer_user_id', $request->user()->id)
            ->with(['workspace'])
            ->latest('id');

        if ($buildingId !== null && $buildingId !== '') {
            $query->where('workspace_id', (int) $buildingId);
        }

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }

        return ReserveRequestResource::collection($query->paginate($perPage))->response();
    }

    public function store(Workspace $workspace, CreateReserveRequest $request): JsonResponse
    {
        $result = $this->reserveRequestService->createOrRemind(
            $workspace,
            $request->user(),
            $request->validated()
        );

        return (new ReserveRequestResource($result['reserve_request']))
            ->additional(['meta' => ['created' => $result['created']]])
            ->response()
            ->setStatusCode($result['created'] ? 201 : 200);
    }

    public function remind(Workspace $workspace, Request $request): JsonResponse
    {
        $reserveRequest = $this->reserveRequestService->sendReminder(
            $workspace,
            $request->user()
        );

        return (new ReserveRequestResource($reserveRequest))->response();
    }

    public function cancel(ReserveRequest $reserveRequest, Request $request): JsonResponse
    {
        Gate::authorize('manageAsCustomer', $reserveRequest);

        $validated = $request->validate([
            'remarks' => ['nullable', 'string', 'max:500'],
        ]);

        $cancelled = $this->reserveRequestService->cancel(
            $reserveRequest,
            $request->user(),
            $validated['remarks'] ?? null
        );

        return (new ReserveRequestResource($cancelled))->response();
    }
}
