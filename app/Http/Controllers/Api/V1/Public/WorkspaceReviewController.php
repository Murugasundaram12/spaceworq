<?php

namespace App\Http\Controllers\Api\V1\Public;

use App\Http\Controllers\Controller;
use App\Http\Requests\Public\StoreWorkspaceReviewRequest;
use App\Models\Workspace;
use App\Models\WorkspaceReview;
use App\Services\Public\WorkspaceVisibilityService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WorkspaceReviewController extends Controller
{
    public function __construct(
        private readonly WorkspaceVisibilityService $visibilityService
    ) {}

    public function index(Workspace $workspace): JsonResponse
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $reviews = $workspace->reviews()
            ->with('user:id,first_name,last_name,name')
            ->where('is_visible', true)
            ->latest()
            ->paginate(15);

        return response()->json([
            'data' => collect($reviews->items())->map(fn (WorkspaceReview $r) => [
                'id' => $r->id,
                'rating' => $r->rating,
                'title' => $r->title,
                'comment' => $r->comment,
                'author_name' => $r->user?->name,
                'created_at' => $r->created_at?->toIso8601String(),
            ]),
            'meta' => [
                'current_page' => $reviews->currentPage(),
                'last_page' => $reviews->lastPage(),
                'total' => $reviews->total(),
            ],
        ]);
    }

    public function upsert(StoreWorkspaceReviewRequest $request, Workspace $workspace): JsonResponse
    {
        if (! $this->visibilityService->isPubliclyVisible($workspace)) {
            return response()->json(['message' => 'Workspace not found.'], 404);
        }

        $validated = $request->validated();

        $review = DB::transaction(function () use ($request, $workspace, $validated): WorkspaceReview {
            $review = WorkspaceReview::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'user_id' => $request->user()->id,
                ],
                [
                    'rating' => $validated['rating'],
                    'title' => $validated['title'] ?? null,
                    'comment' => $validated['comment'] ?? null,
                    'is_visible' => true,
                ]
            );

            $stats = WorkspaceReview::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_visible', true)
                ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_rating')
                ->first();

            $workspace->forceFill([
                'reviews_count' => (int) ($stats->cnt ?? 0),
                'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
            ])->save();

            return $review;
        });

        return response()->json([
            'message' => 'Review submitted successfully.',
            'data' => [
                'id' => $review->id,
                'rating' => $review->rating,
                'title' => $review->title,
                'comment' => $review->comment,
            ],
        ]);
    }
}
