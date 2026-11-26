<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreWorkspaceRequest;
use App\Http\Requests\Provider\UpdateWorkspaceRequest;
use App\Http\Resources\ProviderWorkspaceResource;
use App\Models\Provider;
use App\Models\Workspace;
use App\Models\WorkspaceFloor;
use App\Models\WorkspaceImage;
use App\Services\AuditLogService;
use App\Services\Provider\ProviderWorkspaceService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;

class ProviderWorkspaceController extends Controller
{
    public function __construct(
        private readonly ProviderWorkspaceService $workspaceService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $perPage = max(1, min(50, (int) $request->integer('per_page', 15)));

        $query = Workspace::query()
            ->where('provider_id', $provider->id)
            ->with(['category', 'subcategory', 'city', 'locality', 'listingConfigs', 'images'])
            ->latest('id');

        if ($request->filled('status')) {
            $query->where('status', (string) $request->input('status'));
        }
        if ($request->filled('category_id')) {
            $query->where('category_id', (int) $request->input('category_id'));
        }
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->input('city_id'));
        }
        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(code) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(address_line) LIKE ?', [$term]);
            });
        }

        return ProviderWorkspaceResource::collection($query->paginate($perPage))->response();
    }

    public function store(StoreWorkspaceRequest $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $workspace = $this->workspaceService->create(
            $provider,
            $request->user(),
            $request->validated()
        );

        return (new ProviderWorkspaceResource($workspace))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
        Gate::authorize('view', $workspace);

        return (new ProviderWorkspaceResource(
            $workspace->load(['category', 'subcategory', 'city', 'locality', 'amenities', 'listingConfigs', 'workingHours', 'floors.templateMapping', 'images'])
        ))->response();
    }

    public function update(Workspace $workspace, UpdateWorkspaceRequest $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        $updated = $this->workspaceService->update(
            $workspace,
            $request->user(),
            $request->validated()
        );

        return (new ProviderWorkspaceResource($updated))->response();
    }

    public function destroy(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
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

        return response()->json(['message' => 'Workspace deleted successfully.']);
    }

    public function submitForReview(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        $updated = $this->workspaceService->submitForReview($workspace, $request->user());

        return (new ProviderWorkspaceResource($updated))->response();
    }

    public function uploadImage(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
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

        return response()->json([
            'data' => [
                'id' => $image->id,
                'original_filename' => $image->original_filename,
                'mime_type' => $image->mime_type,
                'size_bytes' => $image->size_bytes,
                'sort_order' => $image->sort_order,
                'is_primary' => $image->is_primary,
                'url' => "/api/v1/workspaces/published/{$workspace->id}/images/{$image->id}/file",
            ],
        ], 201);
    }

    public function deleteImage(Workspace $workspace, WorkspaceImage $image, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
        Gate::authorize('update', $workspace);

        if ($image->workspace_id !== $workspace->id) {
            abort(404, 'Workspace image not found.');
        }

        if ($image->storage_path && Storage::disk($image->disk)->exists($image->storage_path)) {
            Storage::disk($image->disk)->delete($image->storage_path);
        }

        $image->delete();

        $this->auditLogService->log('workspace.image_deleted', $request->user(), $workspace, [
            'image_id' => $image->id,
        ], $workspace->provider_id, $request);

        return response()->json(['message' => 'Workspace image deleted.']);
    }

    public function floors(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
        Gate::authorize('manageFloors', $workspace);

        $floors = $workspace->floors()->with('templateMapping.template')->get();

        return response()->json(['data' => $floors]);
    }

    public function upsertFloors(Workspace $workspace, Request $request): JsonResponse
    {
        $this->assertProviderOwnership($request, $workspace);
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
                [
                    'workspace_id' => $workspace->id,
                    'floor_number' => (int) $row['floor_number'],
                ],
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

        return response()->json(['data' => $workspace->floors()->with('templateMapping')->get()]);
    }

    private function assertProviderOwnership(Request $request, Workspace $workspace): void
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($workspace->provider_id !== $provider->id) {
            abort(403, 'Forbidden: workspace belongs to another provider.');
        }
    }
}
