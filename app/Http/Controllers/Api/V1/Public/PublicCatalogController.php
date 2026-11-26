<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\WorkspaceSearchRequest;
use App\Http\Resources\PublicWorkspaceDetailResource;
use App\Http\Resources\PublicWorkspaceResource;
use App\Models\Amenity;
use App\Models\City;
use App\Models\Workspace;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceImage;
use App\Services\Public\WorkspaceVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

class PublicCatalogController extends Controller
{
    public function __construct(
        private readonly WorkspaceVisibilityService $visibilityService
    ) {
    }

    public function home(): JsonResponse
    {
        $counts = $this->visibilityService->liveCountsByCity();

        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn(City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'image_path' => $city->image_path,
                'is_featured' => $city->is_featured,
                'spaces_count' => (int) ($counts[$city->id] ?? 0),
            ]);

        $categories = WorkspaceCategory::query()
            ->where('is_active', true)
            ->with(['subcategories' => fn($q) => $q->where('is_active', true)])
            ->orderBy('sort_order')
            ->get();

        $amenities = Amenity::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'icon_key']);

        return response()->json([
            'data' => [
                'live_spaces_count' => $this->visibilityService->publishedQuery()->count(),
                'cities' => $cities,
                'categories' => $categories,
                'amenities' => $amenities,
            ],
        ]);
    }

    public function cities(): JsonResponse
    {
        $counts = $this->visibilityService->liveCountsByCity();

        $cities = City::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get()
            ->map(fn(City $city) => [
                'id' => $city->id,
                'name' => $city->name,
                'slug' => $city->slug,
                'state' => $city->state,
                'is_featured' => $city->is_featured,
                'spaces_count' => (int) ($counts[$city->id] ?? 0),
            ]);

        return response()->json(['data' => $cities]);
    }

    public function publishedWorkspaces(WorkspaceSearchRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = $this->visibilityService->publishedQuery()
            ->with(['category', 'subcategory', 'city', 'locality', 'amenities', 'images', 'listingConfigs', 'provider']);

        $this->visibilityService->applyFilters($query, $filters);

        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => PublicWorkspaceResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function expansion(WorkspaceSearchRequest $request): JsonResponse
    {
        $filters = $request->validated();
        $perPage = (int) ($filters['per_page'] ?? 15);

        $query = $this->visibilityService->publishedQuery()
            ->with(['category', 'subcategory', 'city', 'locality', 'amenities', 'images', 'listingConfigs', 'provider'])
            ->whereHas('subcategory', function ($q) use ($filters): void {
                $subtype = strtoupper((string) ($filters['subtype'] ?? $filters['subcategory'] ?? 'ALL'));
                if ($subtype !== 'ALL' && in_array($subtype, ['NEW_BUILDING', 'OFFICE_COMMERCIAL'], true)) {
                    $q->where('code', $subtype);
                } else {
                    $q->whereIn('code', ['NEW_BUILDING', 'OFFICE_COMMERCIAL']);
                }
            });

        $this->visibilityService->applyFilters($query, array_merge($filters, ['subcategory' => null, 'subtype' => null]));
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => PublicWorkspaceResource::collection($paginator->items()),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
            ],
        ]);
    }

    public function suggestions(Request $request): JsonResponse
    {
        $q = trim((string) $request->query('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['data' => []]);
        }

        $term = '%' . strtolower($q) . '%';
        $matches = $this->visibilityService->publishedQuery()
            ->with(['city', 'locality'])
            ->where(function ($query) use ($term): void {
                $query->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(landmark) LIKE ?', [$term])
                    ->orWhereHas('city', fn($cq) => $cq->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('locality', fn($lq) => $lq->whereRaw('LOWER(name) LIKE ?', [$term]));
            })
            ->limit(5)
            ->get()
            ->map(fn(Workspace $ws) => [
                'id' => $ws->id,
                'name' => $ws->name,
                'city' => $ws->city?->name,
                'locality' => $ws->locality?->name,
            ]);

        return response()->json(['data' => $matches]);
    }

    public function amenities(): JsonResponse
    {
        $amenities = Amenity::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get(['id', 'name', 'slug', 'icon_key']);

        return response()->json(['data' => $amenities]);
    }

    public function show(Workspace $workspace): JsonResponse
    {
        if (!$this->visibilityService->isPubliclyVisible($workspace)) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $workspace->load([
            'category',
            'subcategory',
            'city',
            'locality',
            'amenities',
            'images',
            'listingConfigs',
            'workingHours',
            'provider',
        ]);

        return response()->json([
            'data' => new PublicWorkspaceDetailResource($workspace),
        ]);
    }

    public function image(Workspace $workspace, WorkspaceImage $image): StreamedResponse|JsonResponse
    {
        if (!$this->visibilityService->isPubliclyVisible($workspace) || $image->workspace_id !== $workspace->id) {
            return response()->json(['message' => 'Image not found.'], 404);
        }

        $disk = Storage::disk($image->disk ?: 'local');
        if (!$disk->exists($image->storage_path)) {
            return response()->json(['message' => 'Image file not found.'], 404);
        }

        return $disk->response($image->storage_path, $image->original_filename, [
            'Content-Type' => $image->mime_type,
        ]);
    }

    public function compare(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:3'],
            'ids.*' => ['integer'],
        ]);

        $workspaces = $this->visibilityService->publishedQuery()
            ->whereIn('id', $validated['ids'])
            ->with(['category', 'subcategory', 'city', 'locality', 'amenities', 'images', 'listingConfigs', 'provider'])
            ->get();

        return response()->json([
            'data' => PublicWorkspaceResource::collection($workspaces),
        ]);
    }
}
