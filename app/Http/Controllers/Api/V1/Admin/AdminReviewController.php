<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\WorkspaceReview;
use App\Services\Admin\ReviewModerationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    public function __construct(
        private readonly ReviewModerationService $reviewModerationService
    ) {}

    public function index(Request $request): JsonResponse
    {
        return response()->json($this->reviewModerationService->paginate($request));
    }

    public function moderate(WorkspaceReview $review, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'is_visible' => ['required', 'boolean'],
            'moderation_notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $updated = $this->reviewModerationService->moderate(
            $review,
            $request->user(),
            (bool) $validated['is_visible'],
            $validated['moderation_notes'] ?? null,
            $request
        );

        return response()->json(['data' => $updated]);
    }
}
