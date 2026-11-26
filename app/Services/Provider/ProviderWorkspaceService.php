<?php

namespace App\Services\Provider;

use App\Models\City;
use App\Models\Locality;
use App\Models\Provider;
use App\Models\User;
use App\Models\Workspace;
use App\Models\WorkspaceApprovalHistory;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceFloor;
use App\Models\WorkspaceImage;
use App\Models\WorkspaceListingConfig;
use App\Models\WorkspaceSubcategory;
use App\Models\WorkspaceWorkingHour;
use App\Services\AuditLogService;
use App\Services\HtmlSanitizerService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProviderWorkspaceService
{
    public function __construct(
        private readonly HtmlSanitizerService $sanitizer,
        private readonly AuditLogService $auditLogService
    ) {}

    public function create(Provider $provider, User $actor, array $data): Workspace
    {
        $category = WorkspaceCategory::query()->findOrFail($data['category_id']);
        $subcategory = WorkspaceSubcategory::query()->findOrFail($data['subcategory_id']);
        if ($subcategory->category_id !== $category->id) {
            throw ValidationException::withMessages([
                'subcategory_id' => ['Selected subcategory does not belong to the chosen category.'],
            ]);
        }

        $city = City::query()->findOrFail($data['city_id']);
        if (! empty($data['locality_id'])) {
            $locality = Locality::query()->findOrFail($data['locality_id']);
            if ($locality->city_id !== $city->id) {
                throw ValidationException::withMessages([
                    'locality_id' => ['Selected locality does not belong to the chosen city.'],
                ]);
            }
        }

        return DB::transaction(function () use ($provider, $actor, $category, $city, $data): Workspace {
            $requestedStatus = $data['status'] ?? Workspace::STATUS_DRAFT;
            $status = in_array($requestedStatus, [Workspace::STATUS_DRAFT, Workspace::STATUS_IN_REVIEW], true)
                ? $requestedStatus
                : Workspace::STATUS_IN_REVIEW;

            $code = $this->generateWorkspaceCode($category->code, $city->slug);
            $slug = Str::slug($data['name']) . '-' . strtolower(Str::random(5));

            $workspace = Workspace::query()->create([
                'provider_id' => $provider->id,
                'code' => $code,
                'slug' => $slug,
                'name' => $data['name'],
                'generated_post_title' => $data['generated_post_title'] ?? "{$data['name']} in {$city->name}",
                'category_id' => $category->id,
                'subcategory_id' => $data['subcategory_id'],
                'city_id' => $city->id,
                'locality_id' => $data['locality_id'] ?? null,
                'address_line' => $data['address_line'],
                'landmark' => $data['landmark'] ?? null,
                'pincode' => $data['pincode'] ?? null,
                'place_id' => $data['place_id'] ?? null,
                'latitude' => $data['latitude'] ?? null,
                'longitude' => $data['longitude'] ?? null,
                'description_html' => $this->sanitizer->sanitize($data['description_html'] ?? null),
                'total_floors' => $data['total_floors'],
                'total_seats' => $data['total_seats'],
                'available_seats' => $data['available_seats'],
                'is_upcoming' => (bool) ($data['is_upcoming'] ?? false),
                'tentative_availability_date' => $data['tentative_availability_date'] ?? null,
                'gst_enabled' => (bool) ($data['gst_enabled'] ?? false),
                'tax_rate' => $data['tax_rate'] ?? null,
                'is_instant_bookable' => (bool) ($data['is_instant_bookable'] ?? false),
                'status' => $status,
                'approval_status' => Workspace::APPROVAL_PENDING,
                'is_published' => false,
                'created_by' => $actor->id,
                'updated_by' => $actor->id,
            ]);

            if (! empty($data['amenity_ids'])) {
                $workspace->amenities()->sync($data['amenity_ids']);
            }

            if (! empty($data['pricing'])) {
                $this->syncPricing($workspace, $workspace->subcategory_id, $data['pricing']);
            }

            if (! empty($data['working_hours'])) {
                $this->syncWorkingHours($workspace, $data['working_hours']);
            }

            if (! empty($data['floors'])) {
                $this->syncFloors($workspace, $data['floors']);
            } else {
                for ($i = 1; $i <= (int) $workspace->total_floors; $i++) {
                    WorkspaceFloor::query()->create([
                        'workspace_id' => $workspace->id,
                        'floor_number' => $i,
                        'label' => "Floor {$i}",
                        'total_seats' => (int) floor($workspace->total_seats / max(1, (int) $workspace->total_floors)),
                        'is_active' => true,
                    ]);
                }
            }

            WorkspaceApprovalHistory::query()->create([
                'workspace_id' => $workspace->id,
                'actor_user_id' => $actor->id,
                'from_status' => null,
                'to_status' => $status,
                'notes' => 'Workspace created by provider.',
            ]);

            $this->auditLogService->log('workspace.created', $actor, $workspace, [
                'code' => $workspace->code,
                'status' => $status,
            ], $provider->id);

            return $workspace->load(['category', 'subcategory', 'city', 'locality', 'amenities', 'listingConfigs', 'workingHours', 'floors', 'images']);
        });
    }

    public function update(Workspace $workspace, User $actor, array $data): Workspace
    {
        return DB::transaction(function () use ($workspace, $actor, $data): Workspace {
            $categoryId = $data['category_id'] ?? $workspace->category_id;
            $subcategoryId = $data['subcategory_id'] ?? $workspace->subcategory_id;
            $subcategory = WorkspaceSubcategory::query()->findOrFail($subcategoryId);
            if ($subcategory->category_id !== (int) $categoryId) {
                throw ValidationException::withMessages([
                    'subcategory_id' => ['Selected subcategory does not belong to the chosen category.'],
                ]);
            }

            $cityId = $data['city_id'] ?? $workspace->city_id;
            if (! empty($data['locality_id'])) {
                $locality = Locality::query()->findOrFail($data['locality_id']);
                if ($locality->city_id !== (int) $cityId) {
                    throw ValidationException::withMessages([
                        'locality_id' => ['Selected locality does not belong to the chosen city.'],
                    ]);
                }
            }

            $updates = [];
            $scalarFields = [
                'name', 'generated_post_title', 'category_id', 'subcategory_id',
                'city_id', 'locality_id', 'address_line', 'landmark', 'pincode',
                'place_id', 'latitude', 'longitude', 'total_floors', 'total_seats',
                'available_seats', 'is_upcoming', 'tentative_availability_date',
                'gst_enabled', 'tax_rate', 'is_instant_bookable',
            ];

            foreach ($scalarFields as $field) {
                if (array_key_exists($field, $data)) {
                    $updates[$field] = $data[$field];
                }
            }

            if (array_key_exists('description_html', $data)) {
                $updates['description_html'] = $this->sanitizer->sanitize($data['description_html']);
            }

            if (isset($updates['available_seats']) && ($updates['available_seats'] > ($updates['total_seats'] ?? $workspace->total_seats))) {
                throw ValidationException::withMessages([
                    'available_seats' => ['Available seats cannot exceed total seats.'],
                ]);
            }

            // Provider cannot self-approve or publish an unapproved workspace
            if (array_key_exists('status', $data)) {
                $targetStatus = $data['status'];
                if ($targetStatus === Workspace::STATUS_ACTIVE && $workspace->approval_status !== Workspace::APPROVAL_APPROVED) {
                    throw ValidationException::withMessages([
                        'status' => ['Workspace must be approved by Super Admin before becoming ACTIVE.'],
                    ]);
                }
                $updates['status'] = $targetStatus;
                if ($workspace->approval_status === Workspace::APPROVAL_APPROVED) {
                    $updates['is_published'] = $targetStatus === Workspace::STATUS_ACTIVE;
                }
            }

            $updates['updated_by'] = $actor->id;
            $workspace->forceFill($updates)->save();

            if (array_key_exists('amenity_ids', $data)) {
                $workspace->amenities()->sync($data['amenity_ids']);
            }

            if (! empty($data['pricing'])) {
                $this->syncPricing($workspace, $workspace->subcategory_id, $data['pricing']);
            }

            if (! empty($data['working_hours'])) {
                $this->syncWorkingHours($workspace, $data['working_hours']);
            }

            $this->auditLogService->log('workspace.updated', $actor, $workspace, [
                'code' => $workspace->code,
            ], $workspace->provider_id);

            return $workspace->fresh(['category', 'subcategory', 'city', 'locality', 'amenities', 'listingConfigs', 'workingHours', 'floors', 'images']);
        });
    }

    public function submitForReview(Workspace $workspace, User $actor): Workspace
    {
        if ($workspace->status === Workspace::STATUS_IN_REVIEW) {
            return $workspace->fresh();
        }

        $fromStatus = $workspace->status;
        $workspace->forceFill([
            'status' => Workspace::STATUS_IN_REVIEW,
            'approval_status' => Workspace::APPROVAL_PENDING,
            'is_published' => false,
            'updated_by' => $actor->id,
        ])->save();

        WorkspaceApprovalHistory::query()->create([
            'workspace_id' => $workspace->id,
            'actor_user_id' => $actor->id,
            'from_status' => $fromStatus,
            'to_status' => Workspace::STATUS_IN_REVIEW,
            'notes' => 'Submitted for Super Admin review by provider.',
        ]);

        $this->auditLogService->log('workspace.submitted_for_review', $actor, $workspace, [
            'from_status' => $fromStatus,
        ], $workspace->provider_id);

        return $workspace->fresh();
    }

    public function uploadImage(Workspace $workspace, User $actor, UploadedFile $file, bool $isPrimary = false): WorkspaceImage
    {
        $maxImages = (int) config('spaceworq.uploads.max_workspace_images', 5);
        $currentCount = $workspace->images()->count();
        if ($currentCount >= $maxImages) {
            throw ValidationException::withMessages([
                'image' => ["A maximum of {$maxImages} images is allowed per workspace."],
            ]);
        }

        $disk = (string) config('spaceworq.uploads.workspace_images_disk', 'local');
        $ext = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: 'jpg');
        $filename = Str::uuid()->toString() . '.' . $ext;
        $path = "providers/{$workspace->provider_id}/workspaces/{$workspace->id}/{$filename}";

        Storage::disk($disk)->putFileAs(
            "providers/{$workspace->provider_id}/workspaces/{$workspace->id}",
            $file,
            $filename
        );

        if ($isPrimary || $currentCount === 0) {
            $workspace->images()->update(['is_primary' => false]);
        }

        $image = WorkspaceImage::query()->create([
            'workspace_id' => $workspace->id,
            'disk' => $disk,
            'storage_path' => $path,
            'original_filename' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: 'image/jpeg',
            'size_bytes' => $file->getSize() ?: 0,
            'sort_order' => $currentCount + 1,
            'is_primary' => $isPrimary || $currentCount === 0,
        ]);

        $this->auditLogService->log('workspace.image_uploaded', $actor, $image, [
            'workspace_id' => $workspace->id,
        ], $workspace->provider_id);

        return $image;
    }

    private function syncPricing(Workspace $workspace, int $subcategoryId, array $pricing): void
    {
        WorkspaceListingConfig::query()->updateOrCreate(
            [
                'workspace_id' => $workspace->id,
                'subcategory_id' => $subcategoryId,
            ],
            [
                'price_per_day' => $pricing['price_per_day'] ?? null,
                'price_per_month' => $pricing['price_per_month'] ?? null,
                'price_per_hour' => $pricing['price_per_hour'] ?? null,
                'price_per_half_day' => $pricing['price_per_half_day'] ?? null,
                'price_per_sqft' => $pricing['price_per_sqft'] ?? null,
                'price_per_year' => $pricing['price_per_year'] ?? null,
                'min_seats' => $pricing['min_seats'] ?? 1,
                'max_seats' => $pricing['max_seats'] ?? null,
                'is_active' => true,
            ]
        );
    }

    private function syncWorkingHours(Workspace $workspace, array $hours): void
    {
        $dayNames = ['Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday'];

        foreach ($hours as $row) {
            $dow = (int) $row['day_of_week'];
            WorkspaceWorkingHour::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'day_of_week' => $dow,
                ],
                [
                    'day_name' => $row['day_name'] ?? ($dayNames[$dow] ?? 'Day ' . $dow),
                    'open_time' => $row['open_time'] ?? null,
                    'close_time' => $row['close_time'] ?? null,
                    'is_closed' => (bool) ($row['is_closed'] ?? false),
                ]
            );
        }
    }

    private function syncFloors(Workspace $workspace, array $floors): void
    {
        foreach ($floors as $row) {
            WorkspaceFloor::query()->updateOrCreate(
                [
                    'workspace_id' => $workspace->id,
                    'floor_number' => (int) $row['floor_number'],
                ],
                [
                    'label' => $row['label'] ?? ("Floor " . $row['floor_number']),
                    'total_seats' => (int) $row['total_seats'],
                    'is_active' => (bool) ($row['is_active'] ?? true),
                ]
            );
        }
    }

    private function generateWorkspaceCode(string $categoryCode, string $citySlug): string
    {
        $catPrefix = match ($categoryCode) {
            WorkspaceCategory::CODE_LONG_TERM_LEASING => 'LTL',
            WorkspaceCategory::CODE_DAY_PASS => 'DP',
            WorkspaceCategory::CODE_MEETINGS_EVENTS => 'ME',
            WorkspaceCategory::CODE_VIRTUAL_OFFICE => 'VO',
            default => 'SW',
        };

        $cityPrefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', $citySlug) ?: 'IND', 0, 3));
        $suffix = strtoupper(Str::random(4));

        return "{$catPrefix}-{$cityPrefix}{$suffix}";
    }
}
