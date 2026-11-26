<?php

namespace App\Http\Controllers\Api\V1\Customer;

use App\Http\Controllers\Controller;
use App\Http\Resources\PublicWorkspaceResource;
use App\Models\UserFavorite;
use App\Models\Workspace;
use App\Services\AuditLogService;
use App\Services\Public\WorkspaceVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VaultController extends Controller
{
    public function __construct(
        private readonly WorkspaceVisibilityService $visibilityService,
        private readonly AuditLogService $auditLogService
    ) {}

    public function index(Request $request): JsonResponse
    {
        $favoriteIds = UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->orderByDesc('id')
            ->pluck('workspace_id');

        if ($favoriteIds->isEmpty()) {
            return response()->json(['data' => []]);
        }

        $workspaces = $this->visibilityService->publishedQuery()
            ->whereIn('id', $favoriteIds)
            ->with(['category', 'subcategory', 'city', 'locality', 'listingConfigs', 'images'])
            ->get();

        return PublicWorkspaceResource::collection($workspaces)->response();
    }

    public function store(Workspace $workspace, Request $request): JsonResponse
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            abort(404, 'Workspace not found.');
        }

        $favorite = UserFavorite::query()->firstOrCreate([
            'user_id' => $request->user()->id,
            'workspace_id' => $workspace->id,
        ]);

        if ($favorite->wasRecentlyCreated) {
            $this->auditLogService->log('vault.saved', $request->user(), $workspace, [], $workspace->provider_id, $request);
        }

        return response()->json([
            'message' => 'Workspace saved to vault.',
            'data' => [
                'workspace_id' => $workspace->id,
                'saved' => true,
            ],
        ], $favorite->wasRecentlyCreated ? 201 : 200);
    }

    public function destroy(Workspace $workspace, Request $request): JsonResponse
    {
        $deleted = UserFavorite::query()
            ->where('user_id', $request->user()->id)
            ->where('workspace_id', $workspace->id)
            ->delete();

        if ($deleted > 0) {
            $this->auditLogService->log('vault.removed', $request->user(), $workspace, [], $workspace->provider_id, $request);
        }

        return response()->json([
            'message' => 'Workspace removed from vault.',
            'data' => [
                'workspace_id' => $workspace->id,
                'saved' => false,
            ],
        ]);
    }
}
