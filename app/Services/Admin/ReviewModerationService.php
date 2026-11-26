<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceReview;
use App\Services\AuditLogService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReviewModerationService
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function paginate(Request $request): LengthAwarePaginator
    {
        $perPage = max(1, min(50, (int) $request->integer('per_page', 20)));

        $query = WorkspaceReview::query()
            ->with([
                'workspace:id,provider_id,name,code,slug',
                'user:id,name,email',
                'moderator:id,name,email',
            ])
            ->latest('id');

        if ($request->filled('is_visible')) {
            $query->where('is_visible', filter_var($request->input('is_visible'), FILTER_VALIDATE_BOOLEAN));
        }

        if ($request->filled('workspace_id')) {
            $query->where('workspace_id', (int) $request->input('workspace_id'));
        }

        if ($request->filled('rating')) {
            $query->where('rating', (int) $request->input('rating'));
        }

        if ($request->filled('q')) {
            $term = '%' . strtolower(trim((string) $request->input('q'))) . '%';
            $query->where(function ($q) use ($term): void {
                $q->whereRaw('LOWER(COALESCE(title, \'\')) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(COALESCE(comment, \'\')) LIKE ?', [$term]);
            });
        }

        return $query->paginate($perPage);
    }

    public function moderate(
        WorkspaceReview $review,
        User $admin,
        bool $isVisible,
        ?string $moderationNotes = null,
        ?Request $request = null
    ): WorkspaceReview {
        $updated = DB::transaction(function () use ($review, $admin, $isVisible, $moderationNotes): WorkspaceReview {
            $review->forceFill([
                'is_visible' => $isVisible,
                'moderated_by' => $admin->id,
                'moderated_at' => now(),
                'moderation_notes' => $moderationNotes,
            ])->save();

            /** @var Workspace $workspace */
            $workspace = Workspace::query()->lockForUpdate()->findOrFail($review->workspace_id);

            $stats = WorkspaceReview::query()
                ->where('workspace_id', $workspace->id)
                ->where('is_visible', true)
                ->selectRaw('COUNT(*) as cnt, AVG(rating) as avg_rating')
                ->first();

            $workspace->forceFill([
                'reviews_count' => (int) ($stats->cnt ?? 0),
                'rating_avg' => round((float) ($stats->avg_rating ?? 0), 2),
            ])->save();

            return $review->fresh(['workspace:id,name,code', 'user:id,name,email', 'moderator:id,name,email']);
        });

        $this->auditLogService->log('admin.review_moderated', $admin, $updated, [
            'workspace_id' => $updated->workspace_id,
            'is_visible' => $updated->is_visible,
            'moderation_notes' => $moderationNotes,
        ], $updated->workspace?->provider_id, $request);

        return $updated;
    }
}
