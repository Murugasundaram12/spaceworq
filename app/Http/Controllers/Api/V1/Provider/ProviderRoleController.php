<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderRoleRequest;
use App\Http\Resources\ProviderRoleResource;
use App\Models\Provider;
use App\Models\ProviderRole;
use App\Models\Workspace;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class ProviderRoleController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function permissions(): JsonResponse
    {
        return response()->json([
            'data' => ProviderRole::CANONICAL_PERMISSIONS,
        ]);
    }

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $query = ProviderRole::query()
            ->where('provider_id', $provider->id)
            ->orderBy('id');

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }

        return ProviderRoleResource::collection($query->get())->response();
    }

    public function store(StoreProviderRoleRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $validated = $request->validated();

        if (! empty($validated['workspace_id'])) {
            $ws = Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->first();
            if (! $ws) {
                abort(403, 'Forbidden: workspace does not belong to your provider account.');
            }
        }

        $exists = ProviderRole::query()
            ->where('provider_id', $provider->id)
            ->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])
            ->exists();

        if ($exists) {
            throw ValidationException::withMessages([
                'name' => ['A role with this name already exists for this provider.'],
            ]);
        }

        $role = ProviderRole::query()->create([
            'provider_id' => $provider->id,
            'workspace_id' => $validated['workspace_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => array_values(array_unique($validated['permissions'])),
            'is_system' => false,
        ]);

        $this->auditLogService->log('provider.role_created', $request->user(), $role, [
            'role_name' => $role->name,
        ], $provider->id, $request);

        return (new ProviderRoleResource($role))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProviderRole $role, StoreProviderRoleRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        if ($role->provider_id !== $provider->id) {
            abort(403, 'Forbidden: role belongs to another provider.');
        }

        $validated = $request->validated();

        if (! empty($validated['workspace_id'])) {
            $ws = Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->first();
            if (! $ws) {
                abort(403, 'Forbidden: workspace does not belong to your provider account.');
            }
        }

        $role->forceFill([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'description' => $validated['description'] ?? $role->description,
            'workspace_id' => $validated['workspace_id'] ?? $role->workspace_id,
            'permissions' => array_values(array_unique($validated['permissions'])),
        ])->save();

        $this->auditLogService->log('provider.role_updated', $request->user(), $role, [
            'role_name' => $role->name,
        ], $provider->id, $request);

        return (new ProviderRoleResource($role))->response();
    }

    public function destroy(ProviderRole $role, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        if ($role->provider_id !== $provider->id) {
            abort(403, 'Forbidden: role belongs to another provider.');
        }

        if ($role->is_system) {
            throw ValidationException::withMessages([
                'role' => ['System provider roles cannot be deleted.'],
            ]);
        }

        if ($role->providerUsers()->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages([
                'role' => ['Reassign active staff members before deleting this role.'],
            ]);
        }

        $role->delete();

        $this->auditLogService->log('provider.role_deleted', $request->user(), $role, [
            'role_name' => $role->name,
        ], $provider->id, $request);

        return response()->json(['message' => 'Provider role deleted.']);
    }
}
