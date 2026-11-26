<?php

namespace App\Services\Public;

use App\Models\Provider;
use App\Models\Workspace;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class WorkspaceVisibilityService
{
    public function publishedQuery(): Builder
    {
        return Workspace::query()
            ->where('is_published', true)
            ->where('status', Workspace::STATUS_ACTIVE)
            ->where('approval_status', Workspace::APPROVAL_APPROVED)
            ->whereHas('provider', function (Builder $query): void {
                $query->where('status', Provider::STATUS_ACTIVE)
                    ->where('kyc_status', Provider::KYC_VERIFIED);
            });
    }

    public function isPubliclyVisible(Workspace $workspace): bool
    {
        $workspace->loadMissing('provider');

        return $workspace->is_published
            && $workspace->status === Workspace::STATUS_ACTIVE
            && $workspace->approval_status === Workspace::APPROVAL_APPROVED
            && $workspace->provider !== null
            && $workspace->provider->status === Provider::STATUS_ACTIVE
            && $workspace->provider->kyc_status === Provider::KYC_VERIFIED;
    }

    public function applyFilters(Builder $query, array $filters): Builder
    {
        if (! empty($filters['category']) && strtolower((string) $filters['category']) !== 'all') {
            $categoryCode = strtoupper(trim((string) $filters['category']));
            $query->whereHas('category', fn (Builder $q) => $q->where('code', $categoryCode));
        }

        if (! empty($filters['subcategory']) && strtoupper((string) $filters['subcategory']) !== 'ALL') {
            $subCode = strtoupper(trim((string) $filters['subcategory']));
            $query->whereHas('subcategory', fn (Builder $q) => $q->where('code', $subCode));
        }

        if (! empty($filters['city']) && strtolower((string) $filters['city']) !== 'all') {
            $cityVal = trim((string) $filters['city']);
            $query->whereHas('city', function (Builder $q) use ($cityVal): void {
                if (is_numeric($cityVal)) {
                    $q->where('id', (int) $cityVal);
                } else {
                    $q->whereRaw('LOWER(slug) = ?', [strtolower($cityVal)])
                        ->orWhereRaw('LOWER(name) = ?', [strtolower($cityVal)]);
                }
            });
        }

        if (! empty($filters['locality'])) {
            $loc = '%' . strtolower(trim((string) $filters['locality'])) . '%';
            $query->where(function (Builder $q) use ($loc): void {
                $q->whereHas('locality', fn (Builder $lq) => $lq->whereRaw('LOWER(name) LIKE ?', [$loc]))
                    ->orWhereRaw('LOWER(address_line) LIKE ?', [$loc]);
            });
        }

        if (! empty($filters['q'])) {
            $term = '%' . strtolower(trim((string) $filters['q'])) . '%';
            $query->where(function (Builder $q) use ($term): void {
                $q->whereRaw('LOWER(name) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(landmark) LIKE ?', [$term])
                    ->orWhereRaw('LOWER(address_line) LIKE ?', [$term])
                    ->orWhereHas('city', fn (Builder $cq) => $cq->whereRaw('LOWER(name) LIKE ?', [$term]))
                    ->orWhereHas('locality', fn (Builder $lq) => $lq->whereRaw('LOWER(name) LIKE ?', [$term]));
            });
        }

        if (! empty($filters['amenities']) && is_array($filters['amenities'])) {
            foreach ($filters['amenities'] as $amenity) {
                $val = strtolower(trim((string) $amenity));
                if ($val === '') {
                    continue;
                }
                $query->whereHas('amenities', function (Builder $aq) use ($val): void {
                    $aq->whereRaw('LOWER(slug) = ?', [$val])
                        ->orWhereRaw('LOWER(name) = ?', [$val]);
                });
            }
        }

        $sort = $filters['sort'] ?? 'recommended';
        if ($sort === 'rating') {
            $query->orderByDesc('rating_avg')->orderByDesc('reviews_count');
        } elseif ($sort === 'priceLowHigh' || $sort === 'priceHighLow') {
            $dir = $sort === 'priceLowHigh' ? 'ASC' : 'DESC';
            $query->orderByRaw(
                "(SELECT COALESCE(MIN(COALESCE(price_per_month, price_per_day, price_per_hour, price_per_year, price_per_sqft)), 99999999) FROM workspace_listing_configs WHERE workspace_listing_configs.workspace_id = workspaces.id AND workspace_listing_configs.is_active = 1) {$dir}"
            );
        } else {
            $query->orderByDesc('rating_avg')->orderByDesc('id');
        }

        return $query;
    }

    public function liveCountsByCity(): Collection
    {
        return $this->publishedQuery()
            ->selectRaw('city_id, COUNT(*) as live_count')
            ->groupBy('city_id')
            ->pluck('live_count', 'city_id');
    }
}
