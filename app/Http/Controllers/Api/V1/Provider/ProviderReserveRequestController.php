<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderReserveRequestResource;
use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\ReserveRequest;
use App\Notifications\ReserveRequestStatusNotification;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ProviderReserveRequestController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));

        $query = ReserveRequest::query()
            ->where('provider_id', $provider->id)
            ->with(['workspace', 'customer', 'assignedStaff.user'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(request_code) LIKE ?', [$term])
                    ->orWhereHas('customer', fn ($cq) => $cq->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(email) LIKE ?', [$term]));
            });
        }

        return ProviderReserveRequestResource::collection($query->paginate($perPage))->response();
    }

    public function updateStatus(ReserveRequest $reserveRequest, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($reserveRequest->provider_id !== $provider->id) {
            abort(403, 'Forbidden: reserve request belongs to another provider.');
        }

        Gate::authorize('manageAsProvider', $reserveRequest);

        $validated = $request->validate([
            'status' => ['required', 'string', 'in:' . implode(',', ReserveRequest::STATUSES)],
            'remarks' => ['nullable', 'string', 'max:1000'],
            'assigned_provider_user_id' => ['nullable', 'integer', 'exists:provider_users,id'],
        ]);

        if (! empty($validated['assigned_provider_user_id'])) {
            $staff = ProviderUser::query()
                ->where('id', $validated['assigned_provider_user_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $staff) {
                abort(403, 'Forbidden: assigned staff member belongs to another provider.');
            }
        }

        $reserveRequest->forceFill([
            'status' => $validated['status'],
            'remarks' => $validated['remarks'] ?? $reserveRequest->remarks,
            'assigned_provider_user_id' => $validated['assigned_provider_user_id'] ?? $reserveRequest->assigned_provider_user_id,
        ])->save();

        $this->auditLogService->log('provider.reserve_request_updated', $request->user(), $reserveRequest, [
            'status' => $reserveRequest->status,
        ], $provider->id, $request);

        if ($reserveRequest->customer) {
            $reserveRequest->customer->notify(new ReserveRequestStatusNotification($reserveRequest, strtolower($reserveRequest->status)));
        }

        return (new ProviderReserveRequestResource($reserveRequest->fresh(['workspace', 'customer', 'assignedStaff.user'])))->response();
    }

    public function assign(ReserveRequest $reserveRequest, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($reserveRequest->provider_id !== $provider->id) {
            abort(403, 'Forbidden: reserve request belongs to another provider.');
        }

        Gate::authorize('manageAsProvider', $reserveRequest);

        $validated = $request->validate([
            'assigned_provider_user_id' => ['nullable', 'integer', 'exists:provider_users,id'],
        ]);

        if (! empty($validated['assigned_provider_user_id'])) {
            $staff = ProviderUser::query()
                ->where('id', $validated['assigned_provider_user_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $staff) {
                abort(403, 'Forbidden: assigned staff member belongs to another provider.');
            }
        }

        $reserveRequest->forceFill([
            'assigned_provider_user_id' => $validated['assigned_provider_user_id'] ?? null,
        ])->save();

        $this->auditLogService->log('provider.reserve_request_assigned', $request->user(), $reserveRequest, [
            'assigned_provider_user_id' => $reserveRequest->assigned_provider_user_id,
        ], $provider->id, $request);

        return (new ProviderReserveRequestResource($reserveRequest->fresh(['workspace', 'customer', 'assignedStaff.user'])))->response();
    }
}
