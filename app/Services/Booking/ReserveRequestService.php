<?php

namespace App\Services\Booking;

use App\Models\ReserveRequest;
use App\Models\User;
use App\Models\Workspace;
use App\Notifications\ReserveRequestStatusNotification;
use App\Services\AuditLogService;
use App\Services\Public\WorkspaceVisibilityService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ReserveRequestService
{
    public function __construct(
        private readonly WorkspaceVisibilityService $visibilityService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function createOrRemind(Workspace $workspace, User $customer, array $data): array
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            throw ValidationException::withMessages([
                'workspace_id' => ['Workspace is not available for reservation inquiries.'],
            ]);
        }

        return DB::transaction(function () use ($workspace, $customer, $data): array {
            /** @var ReserveRequest|null $existing */
            $existing = ReserveRequest::query()
                ->where('workspace_id', $workspace->id)
                ->where('customer_user_id', $customer->id)
                ->whereNotIn('status', ['Completed', 'Cancelled', 'Rejected'])
                ->lockForUpdate()
                ->first();

            if ($existing) {
                $existing->forceFill([
                    'requested_seats' => $data['requested_seats'] ?? $existing->requested_seats,
                    'move_in_date' => $data['move_in_date'] ?? $existing->move_in_date,
                    'duration_months' => $data['duration_months'] ?? $existing->duration_months,
                    'request_summary' => $data['request_summary'] ?? $existing->request_summary,
                    'reminders_count' => $existing->reminders_count + 1,
                    'last_reminded_at' => now(),
                ])->save();

                $this->auditLogService->log('reserve_request.reminded', $customer, $existing, [
                    'request_code' => $existing->request_code,
                    'reminders_count' => $existing->reminders_count,
                ], $workspace->provider_id);

                return [
                    'reserve_request' => $existing->fresh(['workspace']),
                    'created' => false,
                ];
            }

            $reserveRequest = ReserveRequest::query()->create([
                'request_code' => 'RSV-' . strtoupper(Str::random(8)),
                'workspace_id' => $workspace->id,
                'provider_id' => $workspace->provider_id,
                'customer_user_id' => $customer->id,
                'status' => 'Open',
                'requested_seats' => $data['requested_seats'] ?? 1,
                'move_in_date' => $data['move_in_date'] ?? null,
                'duration_months' => $data['duration_months'] ?? null,
                'request_summary' => $data['request_summary'] ?? null,
            ]);

            $this->auditLogService->log('reserve_request.created', $customer, $reserveRequest, [
                'request_code' => $reserveRequest->request_code,
                'workspace_id' => $workspace->id,
            ], $workspace->provider_id);

            $customer->notify(new ReserveRequestStatusNotification($reserveRequest, 'submitted'));

            return [
                'reserve_request' => $reserveRequest->load('workspace'),
                'created' => true,
            ];
        });
    }

    public function sendReminder(Workspace $workspace, User $customer): ReserveRequest
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            throw ValidationException::withMessages([
                'workspace_id' => ['Workspace is not available.'],
            ]);
        }

        /** @var ReserveRequest|null $reserveRequest */
        $reserveRequest = ReserveRequest::query()
            ->where('workspace_id', $workspace->id)
            ->where('customer_user_id', $customer->id)
            ->whereNotIn('status', ['Completed', 'Cancelled', 'Rejected'])
            ->latest('id')
            ->first();

        if (! $reserveRequest) {
            throw ValidationException::withMessages([
                'reserve_request' => ['No open reservation request exists for this workspace.'],
            ]);
        }

        $reserveRequest->forceFill([
            'reminders_count' => $reserveRequest->reminders_count + 1,
            'last_reminded_at' => now(),
        ])->save();

        $this->auditLogService->log('reserve_request.reminded', $customer, $reserveRequest, [
            'request_code' => $reserveRequest->request_code,
            'reminders_count' => $reserveRequest->reminders_count,
        ], $workspace->provider_id);

        return $reserveRequest->fresh(['workspace']);
    }

    public function cancel(ReserveRequest $reserveRequest, User $actor, ?string $remarks = null): ReserveRequest
    {
        if (in_array($reserveRequest->status, ['Completed', 'Cancelled', 'Rejected'], true)) {
            throw ValidationException::withMessages([
                'status' => ["Reservation request cannot be cancelled from status [{$reserveRequest->status}]."],
            ]);
        }

        $reserveRequest->forceFill([
            'status' => 'Cancelled',
            'remarks' => $remarks ?? $reserveRequest->remarks,
        ])->save();

        $this->auditLogService->log('reserve_request.cancelled', $actor, $reserveRequest, [
            'request_code' => $reserveRequest->request_code,
        ], $reserveRequest->provider_id);

        if ($reserveRequest->customer) {
            $reserveRequest->customer->notify(new ReserveRequestStatusNotification($reserveRequest, 'cancelled'));
        }

        return $reserveRequest->fresh(['workspace']);
    }
}
