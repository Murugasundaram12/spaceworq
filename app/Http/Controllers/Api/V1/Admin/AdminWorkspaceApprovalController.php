<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\ProviderWorkspaceResource;
use App\Models\Provider;
use App\Models\Workspace;
use App\Models\WorkspaceApprovalHistory;
use App\Notifications\ProviderApprovalNotification;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class AdminWorkspaceApprovalController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));

        $query = Workspace::query()
            ->with(['provider:id,company_name,kyc_status,status', 'category:id,code,name', 'subcategory:id,code,name', 'city:id,name,slug', 'listingConfigs'])
            ->latest('id');

        if ($request->filled('approval_status')) {
            $query->where('approval_status', (string) $request->input('approval_status'));
        }
        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('provider_id')) {
            $query->where('provider_id', (int) $request->input('provider_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])->orWhereRaw('LOWER(code) LIKE ?', [$term]);
            });
        }

        return ProviderWorkspaceResource::collection($query->paginate($perPage))->response();
    }

    public function show(Workspace $workspace): JsonResponse
    {
        $workspace->load([
            'provider',
            'category',
            'subcategory',
            'city',
            'locality',
            'amenities',
            'listingConfigs',
            'workingHours',
            'floors',
            'images',
            'approvalHistories.actor:id,name,email',
        ]);

        return (new ProviderWorkspaceResource($workspace))
            ->additional([
                'approval_histories' => $workspace->approvalHistories,
            ])
            ->response();
    }

    public function decide(Workspace $workspace, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'decision' => ['required', 'string', 'in:APPROVE,REJECT,SUSPEND,UNPUBLISH'],
            'notes' => ['nullable', 'string', 'max:1000', 'required_if:decision,REJECT,SUSPEND'],
        ]);

        $workspace->loadMissing('provider.owner');
        $decision = $validated['decision'];

        if ($decision === 'APPROVE') {
            if (! $workspace->provider || $workspace->provider->kyc_status !== Provider::KYC_VERIFIED || $workspace->provider->status !== Provider::STATUS_ACTIVE) {
                throw ValidationException::withMessages([
                    'provider' => ['Provider account must be ACTIVE and KYC VERIFIED before approving a workspace.'],
                ]);
            }
        }

        DB::transaction(function () use ($workspace, $decision, $validated, $request): void {
            $fromStatus = $workspace->status;
            $updates = match ($decision) {
                'APPROVE' => [
                    'status' => Workspace::STATUS_ACTIVE,
                    'approval_status' => Workspace::APPROVAL_APPROVED,
                    'is_published' => true,
                    'approved_by' => $request->user()->id,
                    'approved_at' => now(),
                ],
                'REJECT' => [
                    'status' => Workspace::STATUS_REJECTED,
                    'approval_status' => Workspace::APPROVAL_REJECTED,
                    'is_published' => false,
                ],
                'SUSPEND' => [
                    'status' => Workspace::STATUS_SUSPENDED,
                    'is_published' => false,
                ],
                'UNPUBLISH' => [
                    'status' => Workspace::STATUS_INACTIVE,
                    'is_published' => false,
                ],
            };

            $updates['updated_by'] = $request->user()->id;
            $workspace->forceFill($updates)->save();

            WorkspaceApprovalHistory::query()->create([
                'workspace_id' => $workspace->id,
                'actor_user_id' => $request->user()->id,
                'from_status' => $fromStatus,
                'to_status' => $updates['status'],
                'notes' => $validated['notes'] ?? "Workspace decision: {$decision}",
            ]);
        });

        $this->auditLogService->log('admin.workspace_' . strtolower($decision), $request->user(), $workspace, [
            'decision' => $decision,
            'notes' => $validated['notes'] ?? null,
        ], $workspace->provider_id, $request);

        if ($workspace->provider?->owner) {
            $workspace->provider->owner->notify(new ProviderApprovalNotification(
                'Workspace',
                $workspace->id,
                $workspace->name,
                $decision,
                $validated['notes'] ?? null
            ));
        }

        return (new ProviderWorkspaceResource($workspace->fresh(['category', 'subcategory', 'city', 'listingConfigs'])))->response();
    }
}
