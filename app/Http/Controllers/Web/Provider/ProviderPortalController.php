<?php

namespace App\Http\Controllers\Web\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreLayoutTemplateRequest;
use App\Http\Requests\Provider\StoreProviderRoleRequest;
use App\Http\Requests\Provider\StoreProviderStaffRequest;
use App\Http\Requests\Provider\StoreWorkspaceRequest;
use App\Http\Requests\Provider\UpdateWorkspaceRequest;
use App\Http\Requests\Web\UpdateWebProviderProfileRequest;
use App\Models\Amenity;
use App\Models\City;
use App\Models\FloorTemplateMapping;
use App\Models\LayoutTemplate;
use App\Models\Provider;
use App\Models\ProviderDocument;
use App\Models\ProviderRole;
use App\Models\ProviderUser;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceFloor;
use App\Models\WorkspaceImage;
use App\Services\AuditLogService;
use App\Services\Provider\ProviderOnboardingService;
use App\Services\Provider\ProviderWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProviderPortalController extends Controller
{
    public function __construct(
        private readonly ProviderOnboardingService $onboardingService,
        private readonly ProviderWorkspaceService $workspaceService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function status(Request $request): View|Response|JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('view', $provider);

        $payload = [
            'status' => $provider->status,
            'status_label' => $provider->statusLabel(),
            'kyc_status' => $provider->kyc_status,
            'kyc_status_label' => $provider->kycStatusLabel(),
            'status_reason' => $provider->status_reason,
            'kyc_notes' => $provider->kyc_notes,
            'submitted_at' => $provider->submitted_at?->toIso8601String(),
            'verified_at' => $provider->verified_at?->toIso8601String(),
            'is_verified_and_active' => $provider->isVerifiedAndActive(),
        ];

        if ($request->expectsJson()) {
            return response()->json(['data' => $payload]);
        }

        return $this->renderPortalView('vendor.status', [
            'provider' => $provider,
            'statusSummary' => $payload,
        ]);
    }

    public function showProfile(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('view', $provider);

        $provider->load(['settings', 'documents', 'city']);

        return $this->renderPortalView('vendor.profile', [
            'provider' => $provider,
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'canEditOnboarding' => $provider->status !== Provider::STATUS_SUSPENDED
                && Gate::allows('update', $provider),
        ]);
    }

    public function updateProfile(UpdateWebProviderProfileRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $updated = $this->onboardingService->updateProfile(
            $provider,
            $request->user(),
            $request->validated(),
            $request
        );

        $message = 'Company profile saved and application submitted for review.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $updated->status,
                'submitted_at' => $updated->submitted_at?->toIso8601String(),
            ]);
        }

        return back()->with('status', $message);
    }

    public function uploadDocument(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        $maxKb = (int) config('spaceworq.uploads.max_document_kb', 10240);
        $validated = $request->validate([
            'document_type' => ['required', 'string', 'in:GST_CERTIFICATE,PAN_CARD,COI,TRADE_LICENSE,ADDRESS_PROOF,OTHER'],
            'document' => ['required', 'file', 'mimes:pdf,jpg,jpeg,png', "max:{$maxKb}"],
        ]);

        $doc = $this->onboardingService->uploadDocument(
            $provider,
            $request->user(),
            $validated['document_type'],
            $validated['document'],
            $request
        );

        $message = 'KYC document uploaded securely.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'document_id' => $doc->id,
                'kyc_status' => $provider->fresh()->kyc_status,
            ], 201);
        }

        return back()->with('status', $message);
    }

    public function downloadDocument(ProviderDocument $document, Request $request): StreamedResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        Gate::authorize('update', $provider);

        return $this->onboardingService->downloadDocument($document, $provider);
    }

    public function dashboard(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $workspaceBase = Workspace::query()->where('provider_id', $provider->id);

        return $this->renderPortalView('vendor.dashboard', [
            'provider' => $provider,
            'metrics' => [
                'users_count' => ProviderUser::query()->where('provider_id', $provider->id)->where('status', ProviderUser::STATUS_ACTIVE)->count(),
                'workspaces_count' => (clone $workspaceBase)->count(),
                'floors_count' => WorkspaceFloor::query()->whereHas('workspace', fn ($q) => $q->where('provider_id', $provider->id))->count(),
                'templates_count' => LayoutTemplate::query()->where('provider_id', $provider->id)->count(),
                'total_seats' => (int) (clone $workspaceBase)->sum('total_seats'),
            ],
            'workspaces' => (clone $workspaceBase)->with(['city:id,name', 'category:id,name'])->latest('id')->get(),
        ]);
    }

    public function workspaces(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $workspaces = Workspace::query()
            ->where('provider_id', $provider->id)
            ->with(['category', 'subcategory', 'city', 'locality', 'amenities', 'floors'])
            ->latest('id')
            ->get();

        return $this->renderPortalView('vendor.workspaces', [
            'provider' => $provider,
            'workspaces' => $workspaces,
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
            'categories' => WorkspaceCategory::query()->where('is_active', true)->with('subcategories')->orderBy('sort_order')->get(),
            'amenities' => Amenity::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function storeWorkspace(StoreWorkspaceRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $workspace = $this->workspaceService->create(
            $provider,
            $request->user(),
            $request->validated()
        );

        $message = 'Workspace created successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['id' => $workspace->id]], 201);
        }

        return back()->with('status', $message);
    }

    public function updateWorkspace(Workspace $workspace, UpdateWorkspaceRequest $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        $this->workspaceService->update($workspace, $request->user(), $request->validated());
        $message = 'Workspace updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['id' => $workspace->id]]);
        }

        return back()->with('status', $message);
    }

    public function destroyWorkspace(Workspace $workspace, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('delete', $workspace);

        $workspace->forceFill([
            'is_published' => false,
            'status' => Workspace::STATUS_INACTIVE,
            'updated_by' => $request->user()->id,
        ])->save();

        $workspace->delete();

        $this->auditLogService->log('workspace.deleted', $request->user(), $workspace, [
            'code' => $workspace->code,
        ], $workspace->provider_id, $request);

        $message = 'Workspace deleted successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function submitWorkspaceForReview(Workspace $workspace, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        $this->workspaceService->submitForReview($workspace, $request->user());
        $message = 'Workspace submitted for Super Admin review.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function uploadWorkspaceImage(Workspace $workspace, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        $maxKb = (int) config('spaceworq.uploads.max_image_kb', 5120);
        $validated = $request->validate([
            'image' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', "max:{$maxKb}"],
            'is_primary' => ['sometimes', 'boolean'],
        ]);

        $image = $this->workspaceService->uploadImage(
            $workspace,
            $request->user(),
            $validated['image'],
            (bool) ($validated['is_primary'] ?? false)
        );

        $message = 'Workspace image uploaded.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'image_id' => $image->id], 201);
        }

        return back()->with('status', $message);
    }

    public function deleteWorkspaceImage(Workspace $workspace, WorkspaceImage $image, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        if ($image->workspace_id !== $workspace->id) {
            abort(404, 'Workspace image not found.');
        }

        if ($image->storage_path && Storage::disk($image->disk)->exists($image->storage_path)) {
            Storage::disk($image->disk)->delete($image->storage_path);
        }

        $image->delete();
        $this->auditLogService->log('workspace.image_deleted', $request->user(), $workspace, ['image_id' => $image->id], $workspace->provider_id, $request);

        $message = 'Workspace image deleted.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function upsertWorkspaceFloors(Workspace $workspace, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertWorkspaceOwnership($request, $workspace);
        Gate::authorize('manageFloors', $workspace);

        $validated = $request->validate([
            'floors' => ['required', 'array', 'min:1', 'max:200'],
            'floors.*.floor_number' => ['required', 'integer', 'min:0', 'max:200'],
            'floors.*.label' => ['nullable', 'string', 'max:100'],
            'floors.*.total_seats' => ['required', 'integer', 'min:0', 'max:10000'],
            'floors.*.is_active' => ['sometimes', 'boolean'],
        ]);

        foreach ($validated['floors'] as $row) {
            WorkspaceFloor::query()->updateOrCreate(
                ['workspace_id' => $workspace->id, 'floor_number' => (int) $row['floor_number']],
                [
                    'label' => $row['label'] ?? ('Floor ' . $row['floor_number']),
                    'total_seats' => (int) $row['total_seats'],
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]
            );
        }

        $this->auditLogService->log('workspace.floors_updated', $request->user(), $workspace, [
            'floors_count' => count($validated['floors']),
        ], $workspace->provider_id, $request);

        $message = 'Workspace floors updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function users(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->renderPortalView('vendor.users', [
            'provider' => $provider,
            'staff' => ProviderUser::query()->where('provider_id', $provider->id)->with(['user', 'providerRole', 'workspace'])->orderByDesc('is_owner')->get(),
            'roles' => ProviderRole::query()->where('provider_id', $provider->id)->orderBy('name')->get(),
            'workspaces' => Workspace::query()->where('provider_id', $provider->id)->orderBy('name')->get(),
        ]);
    }

    public function storeStaff(StoreProviderStaffRequest $request): JsonResponse|RedirectResponse
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
            $wsExists = Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->exists();
            if (! $wsExists) {
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

            $exists = ProviderUser::query()->where('provider_id', $provider->id)->where('user_id', $user->id)->exists();
            if ($exists) {
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

        $this->auditLogService->log('provider.staff_added', $request->user(), $membership, ['role_name' => $role->name], $provider->id, $request);
        $message = 'Staff member registered successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['id' => $membership->id]], 201);
        }

        return back()->with('status', $message);
    }

    public function updateStaff(ProviderUser $staff, Request $request): JsonResponse|RedirectResponse
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
            throw ValidationException::withMessages(['status' => ['Provider owner cannot be deactivated.']]);
        }

        $updates = [];
        if (array_key_exists('provider_role_id', $validated)) {
            $role = ProviderRole::query()->where('id', $validated['provider_role_id'])->where('provider_id', $provider->id)->first();
            if (! $role) {
                abort(403, 'Forbidden: role does not belong to your provider account.');
            }
            $updates['provider_role_id'] = $role->id;
            $updates['role_name'] = $role->name;
        }
        if (array_key_exists('workspace_id', $validated)) {
            if ($validated['workspace_id'] !== null && ! Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->exists()) {
                abort(403, 'Forbidden: workspace does not belong to your provider account.');
            }
            $updates['workspace_id'] = $validated['workspace_id'];
        }
        if (array_key_exists('status', $validated)) {
            $updates['status'] = $validated['status'];
        }

        $staff->forceFill($updates)->save();
        $this->auditLogService->log('provider.staff_updated', $request->user(), $staff, $updates, $provider->id, $request);
        $message = 'Staff member updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroyStaff(ProviderUser $staff, Request $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($staff->provider_id !== $provider->id) {
            abort(403, 'Forbidden: staff member belongs to another provider.');
        }
        if ($staff->is_owner) {
            throw ValidationException::withMessages(['staff' => ['Provider owner cannot be removed.']]);
        }

        $staff->delete();
        $this->auditLogService->log('provider.staff_removed', $request->user(), $staff, [], $provider->id, $request);
        $message = 'Staff member removed.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function roles(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->renderPortalView('vendor.roles', [
            'provider' => $provider,
            'roles' => ProviderRole::query()->where('provider_id', $provider->id)->orderBy('id')->get(),
            'permissions' => ProviderRole::CANONICAL_PERMISSIONS,
        ]);
    }

    public function storeRole(StoreProviderRoleRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $validated = $request->validated();

        if (! empty($validated['workspace_id']) && ! Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->exists()) {
            abort(403, 'Forbidden: workspace does not belong to your provider account.');
        }

        if (ProviderRole::query()->where('provider_id', $provider->id)->whereRaw('LOWER(name) = ?', [strtolower($validated['name'])])->exists()) {
            throw ValidationException::withMessages(['name' => ['A role with this name already exists for this provider.']]);
        }

        $role = ProviderRole::query()->create([
            'provider_id' => $provider->id,
            'workspace_id' => $validated['workspace_id'] ?? null,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'permissions' => array_values(array_unique($validated['permissions'])),
            'is_system' => false,
        ]);

        $this->auditLogService->log('provider.role_created', $request->user(), $role, ['role_name' => $role->name], $provider->id, $request);
        $message = 'Role created successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['id' => $role->id]], 201);
        }

        return back()->with('status', $message);
    }

    public function updateRole(ProviderRole $role, StoreProviderRoleRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($role->provider_id !== $provider->id) {
            abort(403, 'Forbidden: role belongs to another provider.');
        }

        $validated = $request->validated();
        if (! empty($validated['workspace_id']) && ! Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->exists()) {
            abort(403, 'Forbidden: workspace does not belong to your provider account.');
        }

        $role->forceFill([
            'name' => $role->is_system ? $role->name : $validated['name'],
            'description' => $validated['description'] ?? $role->description,
            'workspace_id' => $validated['workspace_id'] ?? $role->workspace_id,
            'permissions' => array_values(array_unique($validated['permissions'])),
        ])->save();

        $this->auditLogService->log('provider.role_updated', $request->user(), $role, ['role_name' => $role->name], $provider->id, $request);
        $message = 'Role updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroyRole(ProviderRole $role, Request $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($role->provider_id !== $provider->id) {
            abort(403, 'Forbidden: role belongs to another provider.');
        }
        if ($role->is_system) {
            throw ValidationException::withMessages(['role' => ['System provider roles cannot be deleted.']]);
        }
        if ($role->providerUsers()->where('status', 'ACTIVE')->exists()) {
            throw ValidationException::withMessages(['role' => ['Reassign active staff members before deleting this role.']]);
        }

        $role->delete();
        $this->auditLogService->log('provider.role_deleted', $request->user(), $role, ['role_name' => $role->name], $provider->id, $request);
        $message = 'Role deleted.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function templates(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->renderPortalView('vendor.template-management', [
            'provider' => $provider,
            'templates' => LayoutTemplate::query()->where('provider_id', $provider->id)->latest('id')->get(),
            'floors' => WorkspaceFloor::query()->whereHas('workspace', fn ($q) => $q->where('provider_id', $provider->id))->with('workspace:id,name')->get(),
        ]);
    }

    public function storeTemplate(StoreLayoutTemplateRequest $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $validated = $request->validated();
        $elements = $validated['elements'];
        $totalSeats = array_sum(array_map(fn (array $el) => (int) ($el['seats'] ?? 0), $elements));

        $template = LayoutTemplate::query()->create([
            'provider_id' => $provider->id,
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'elements' => $elements,
            'elements_count' => count($elements),
            'total_seats' => $totalSeats,
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->log('provider.template_created', $request->user(), $template, [
            'elements_count' => $template->elements_count,
            'total_seats' => $template->total_seats,
        ], $provider->id, $request);

        $message = 'Layout template saved.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message, 'data' => ['id' => $template->id]], 201);
        }

        return back()->with('status', $message);
    }

    public function updateTemplate(LayoutTemplate $template, StoreLayoutTemplateRequest $request): JsonResponse|RedirectResponse
    {
        $this->assertTemplateOwnership($request, $template);
        Gate::authorize('manage', $template);

        $validated = $request->validated();
        $elements = $validated['elements'];
        $totalSeats = array_sum(array_map(fn (array $el) => (int) ($el['seats'] ?? 0), $elements));

        $template->forceFill([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'elements' => $elements,
            'elements_count' => count($elements),
            'total_seats' => $totalSeats,
        ])->save();

        $this->auditLogService->log('provider.template_updated', $request->user(), $template, [
            'elements_count' => $template->elements_count,
        ], $template->provider_id, $request);

        $message = 'Layout template updated.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function destroyTemplate(LayoutTemplate $template, Request $request): JsonResponse|RedirectResponse
    {
        $this->assertTemplateOwnership($request, $template);
        Gate::authorize('manage', $template);

        $template->delete();
        $this->auditLogService->log('provider.template_deleted', $request->user(), $template, [], $template->provider_id, $request);
        $message = 'Layout template deleted.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function templateMappings(Request $request): View|Response
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        return $this->renderPortalView('vendor.template-mapping', [
            'provider' => $provider,
            'workspaces' => Workspace::query()->where('provider_id', $provider->id)->with('floors.templateMapping.template')->get(),
            'templates' => LayoutTemplate::query()->where('provider_id', $provider->id)->orderBy('name')->get(),
        ]);
    }

    public function mapTemplate(Request $request): JsonResponse|RedirectResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $validated = $request->validate([
            'workspace_id' => ['required', 'integer', 'exists:workspaces,id'],
            'workspace_floor_id' => ['required', 'integer', 'exists:workspace_floors,id'],
            'layout_template_id' => ['required', 'integer', 'exists:layout_templates,id'],
        ]);

        $workspace = Workspace::query()->where('id', $validated['workspace_id'])->where('provider_id', $provider->id)->first();
        if (! $workspace) {
            abort(403, 'Forbidden: workspace belongs to another provider.');
        }

        $floor = WorkspaceFloor::query()->where('id', $validated['workspace_floor_id'])->where('workspace_id', $workspace->id)->first();
        if (! $floor) {
            throw ValidationException::withMessages([
                'workspace_floor_id' => ['Selected floor does not belong to the chosen workspace.'],
            ]);
        }

        $template = LayoutTemplate::query()->where('id', $validated['layout_template_id'])->where('provider_id', $provider->id)->first();
        if (! $template) {
            abort(403, 'Forbidden: layout template belongs to another provider.');
        }

        $mapping = FloorTemplateMapping::query()->updateOrCreate(
            ['workspace_floor_id' => $floor->id],
            [
                'provider_id' => $provider->id,
                'workspace_id' => $workspace->id,
                'layout_template_id' => $template->id,
                'mapped_by' => $request->user()->id,
            ]
        );

        $this->auditLogService->log('provider.floor_template_mapped', $request->user(), $mapping, [
            'workspace_id' => $workspace->id,
            'workspace_floor_id' => $floor->id,
            'layout_template_id' => $template->id,
        ], $provider->id, $request);

        $message = 'Template mapped to floor.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message], 201);
        }

        return back()->with('status', $message);
    }

    private function assertWorkspaceOwnership(Request $request, Workspace $workspace): void
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($workspace->provider_id !== $provider->id) {
            abort(403, 'Forbidden: workspace belongs to another provider.');
        }
    }

    private function assertTemplateOwnership(Request $request, LayoutTemplate $template): void
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($template->provider_id !== $provider->id) {
            abort(403, 'Forbidden: layout template belongs to another provider.');
        }
    }
}
