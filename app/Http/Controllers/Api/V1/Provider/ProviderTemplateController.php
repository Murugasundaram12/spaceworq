<?php

namespace App\Http\Controllers\Api\V1\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Provider\StoreLayoutTemplateRequest;
use App\Http\Resources\LayoutTemplateResource;
use App\Models\FloorTemplateMapping;
use App\Models\LayoutTemplate;
use App\Models\Provider;
use App\Models\Workspace;
use App\Models\WorkspaceFloor;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class ProviderTemplateController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));

        $templates = LayoutTemplate::query()
            ->where('provider_id', $provider->id)
            ->latest('id')
            ->paginate($perPage);

        return LayoutTemplateResource::collection($templates)->response();
    }

    public function store(StoreLayoutTemplateRequest $request): JsonResponse
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

        return (new LayoutTemplateResource($template))
            ->response()
            ->setStatusCode(201);
    }

    public function show(LayoutTemplate $template, Request $request): JsonResponse
    {
        $this->assertOwnership($request, $template);
        Gate::authorize('manage', $template);

        return (new LayoutTemplateResource($template))->response();
    }

    public function update(LayoutTemplate $template, StoreLayoutTemplateRequest $request): JsonResponse
    {
        $this->assertOwnership($request, $template);
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

        return (new LayoutTemplateResource($template))->response();
    }

    public function destroy(LayoutTemplate $template, Request $request): JsonResponse
    {
        $this->assertOwnership($request, $template);
        Gate::authorize('manage', $template);

        $template->delete();

        $this->auditLogService->log('provider.template_deleted', $request->user(), $template, [], $template->provider_id, $request);

        return response()->json(['message' => 'Layout template deleted.']);
    }

    public function clone(LayoutTemplate $template, Request $request): JsonResponse
    {
        $this->assertOwnership($request, $template);
        Gate::authorize('manage', $template);

        $copy = LayoutTemplate::query()->create([
            'provider_id' => $template->provider_id,
            'name' => $request->input('name', 'Copy of ' . $template->name),
            'description' => $template->description,
            'elements' => $template->elements,
            'elements_count' => $template->elements_count,
            'total_seats' => $template->total_seats,
            'created_by' => $request->user()->id,
        ]);

        $this->auditLogService->log('provider.template_cloned', $request->user(), $copy, [
            'source_template_id' => $template->id,
        ], $template->provider_id, $request);

        return (new LayoutTemplateResource($copy))
            ->response()
            ->setStatusCode(201);
    }

    public function mappings(Request $request): JsonResponse
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');

        $query = FloorTemplateMapping::query()
            ->where('provider_id', $provider->id)
            ->with(['workspace:id,code,name', 'floor:id,floor_number,label,total_seats', 'template:id,name,elements_count,total_seats'])
            ->latest('id');

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function mapFloor(Request $request): JsonResponse
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
            [
                'workspace_floor_id' => $floor->id,
            ],
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

        return response()->json([
            'data' => $mapping->load(['workspace:id,code,name', 'floor:id,floor_number,label', 'template:id,name,elements_count,total_seats']),
        ], 201);
    }

    private function assertOwnership(Request $request, LayoutTemplate $template): void
    {
        /** @var Provider $provider */
        $provider = $request->attributes->get('current_provider');
        if ($template->provider_id !== $provider->id) {
            abort(403, 'Forbidden: layout template belongs to another provider.');
        }
    }
}
