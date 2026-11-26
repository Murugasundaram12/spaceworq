<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreProviderStaffRequest;
use App\Http\Resources\ProviderUserResource;
use App\Models\Provider;
use App\Models\ProviderRole;
use App\Models\ProviderUser;
use App\Models\User;
use App\Models\Workspace;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProviderStaffController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));

        $query = ProviderUser::query()
            ->where('provider_id', $provider->id)
            ->with(['user', 'providerRole', 'workspace'])
            ->orderByDesc('is_owner')
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }

        return ProviderUserResource::collection($query->paginate($perPage))->response();
    }

    public function store(StoreProviderStaffRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $validated = $request->validated();

        $role = ProviderRole::query()
            ->where('id', $validated['provider_role_id'])
            ->where('provider_id', $provider->id)
            ->first();

        if (! $role) {
            abort(403, 'Forbidden: selected role does not belong to your provider account.');
        }

        if (! empty($validated['workspace_id'])) {
            $workspace = Workspace::query()
                ->where('id', $validated['workspace_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $workspace) {
                abort(403, 'Forbidden: selected workspace does not belong to your provider account.');
            }
        }

        $membership = DB::transaction(function () use ($provider, $role, $validated, $request): ProviderUser {
            $email = strtolower(trim($validated['email']));
            $user = User::query()->whereRaw('LOWER(email) = ?', [$email])->first();

            if ($user && $user->user_type !== User::TYPE_PROVIDER) {
                throw ValidationException::withMessages([
                    'email' => ['An account with this email already exists for a non-provider portal type.'],
                ]);
            }

            if (! $user) {
                $user = User::query()->create([
                    'first_name' => $validated['first_name'],
                    'last_name' => $validated['last_name'] ?? '',
                    'name' => trim($validated['first_name'] . ' ' . ($validated['last_name'] ?? '')),
                    'email' => $email,
                    'mobile' => $validated['mobile'] ?? null,
                    'user_type' => User::TYPE_PROVIDER,
                    'status' => User::STATUS_ACTIVE,
                    'email_verified_at' => now(),
                    'password' => $validated['password'] ?? Str::random(16),
                ]);
                $user->assignRole('provider_staff');
            }

            $existingMembership = ProviderUser::query()
                ->where('provider_id', $provider->id)
                ->where('user_id', $user->id)
                ->first();

            if ($existingMembership) {
                throw ValidationException::withMessages([
                    'email' => ['User is already a staff member of this provider.'],
                ]);
            }

            return ProviderUser::query()->create([
                'provider_id' => $provider->id,
                'user_id' => $user->id,
                'workspace_id' => $validated['workspace_id'] ?? null,
                'provider_role_id' => $role->id,
                'role_name' => $role->name,
                'is_owner' => false,
                'status' => ProviderUser::STATUS_ACTIVE,
                'invited_by' => $request->user()->id,
            ]);
        });

        $this->auditLogService->log('provider.staff_added', $request->user(), $membership, [
            'role_name' => $role->name,
        ], $provider->id, $request);

        return (new ProviderUserResource($membership->load(['user', 'providerRole', 'workspace'])))
            ->response()
            ->setStatusCode(201);
    }

    public function update(ProviderUser $staff, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($staff->provider_id !== $provider->id) {
            abort(403, 'Forbidden: staff member belongs to another provider.');
        }

        $validated = $request->validate([
            'provider_role_id' => ['sometimes', 'integer', 'exists:provider_roles,id'],
            'workspace_id' => ['sometimes', 'nullable', 'integer', 'exists:workspaces,id'],
            'status' => ['sometimes', 'string', 'in:' . ProviderUser::STATUS_ACTIVE . ',' . ProviderUser::STATUS_INACTIVE],
        ]);

        if ($staff->is_owner && ($validated['status'] ?? $staff->status) !== ProviderUser::STATUS_ACTIVE) {
            throw ValidationException::withMessages([
                'status' => ['Provider owner cannot be deactivated.'],
            ]);
        }

        $updates = [];
        if (array_key_exists('provider_role_id', $validated)) {
            $role = ProviderRole::query()
                ->where('id', $validated['provider_role_id'])
                ->where('provider_id', $provider->id)
                ->first();
            if (! $role) {
                abort(403, 'Forbidden: role does not belong to your provider account.');
            }
            $updates['provider_role_id'] = $role->id;
            $updates['role_name'] = $role->name;
        }

        if (array_key_exists('workspace_id', $validated)) {
            if ($validated['workspace_id'] !== null) {
                $ws = Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->first();
                if (! $ws) {
                    abort(403, 'Forbidden: workspace does not belong to your provider account.');
                }
            }
            $updates['workspace_id'] = $validated['workspace_id'];
        }

        if (array_key_exists('status', $validated)) {
            $updates['status'] = $validated['status'];
        }

        $staff->forceFill($updates)->save();

        $this->auditLogService->log('provider.staff_updated', $request->user(), $staff, $updates, $provider->id, $request);

        return (new ProviderUserResource($staff->fresh(['user', 'providerRole', 'workspace'])))->response();
    }

    public function destroy(ProviderUser $staff, Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($staff->provider_id !== $provider->id) {
            abort(403, 'Forbidden: staff member belongs to another provider.');
        }

        if ($staff->is_owner) {
            throw ValidationException::withMessages([
                'staff' => ['Provider owner cannot be removed.'],
            ]);
        }

        $staff->delete();

        $this->auditLogService->log('provider.staff_removed', $request->user(), $staff, [], $provider->id, $request);

        return response()->json(['message' => 'Staff member removed.']);
    }
}
