<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Models\Amenity;
use App\Models\City;
use App\Models\Locality;
use App\Models\WorkspaceCategory;
use App\Models\WorkspaceSubcategory;
use App\Services\AuditLogService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class AdminMasterDataController extends Controller
{
    public function __construct(
        private readonly AuditLogService $auditLogService
    ) {}

    public function cities(): JsonResponse
    {
        return response()->json(['data' => City::query()->orderBy('sort_order')->get()]);
    }

    public function storeCity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:cities,slug'],
            'state' => ['nullable', 'string', 'max:100'],
            'country' => ['nullable', 'string', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $city = City::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'state' => $validated['state'] ?? null,
            'country' => $validated['country'] ?? 'India',
            'is_featured' => (bool) ($validated['is_featured'] ?? false),
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'sort_order' => $validated['sort_order'] ?? 0,
        ]);

        $this->auditLogService->log('admin.city_created', $request->user(), $city, ['name' => $city->name], null, $request);

        return response()->json(['data' => $city], 201);
    }

    public function updateCity(City $city, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'slug' => ['sometimes', 'string', 'max:120', Rule::unique('cities', 'slug')->ignore($city->id)],
            'state' => ['sometimes', 'nullable', 'string', 'max:100'],
            'is_featured' => ['sometimes', 'boolean'],
            'is_active' => ['sometimes', 'boolean'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
        ]);

        $city->forceFill($validated)->save();

        $this->auditLogService->log('admin.city_updated', $request->user(), $city, [], null, $request);

        return response()->json(['data' => $city]);
    }

    public function localities(Request $request): JsonResponse
    {
        $query = Locality::query()->with('city:id,name,slug')->orderBy('name');
        if ($request->filled('city_id')) {
            $query->where('city_id', (int) $request->input('city_id'));
        }

        return response()->json(['data' => $query->get()]);
    }

    public function storeLocality(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'city_id' => ['required', 'integer', 'exists:cities,id'],
            'name' => ['required', 'string', 'max:120'],
            'slug' => ['nullable', 'string', 'max:140'],
            'pincode' => ['nullable', 'string', 'max:15'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $locality = Locality::query()->create([
            'city_id' => $validated['city_id'],
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'pincode' => $validated['pincode'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->auditLogService->log('admin.locality_created', $request->user(), $locality, [], null, $request);

        return response()->json(['data' => $locality->load('city:id,name,slug')], 201);
    }

    public function categories(): JsonResponse
    {
        return response()->json([
            'data' => WorkspaceCategory::query()->with('subcategories')->orderBy('sort_order')->get(),
        ]);
    }

    public function storeCategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:workspace_categories,code'],
            'name' => ['required', 'string', 'max:120'],
            'subtitle' => ['nullable', 'string', 'max:200'],
            'description' => ['nullable', 'string', 'max:500'],
            'default_booking_mode' => ['nullable', 'string', 'in:instant_book,reserve_request'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $category = WorkspaceCategory::query()->create(array_merge($validated, [
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]));

        $this->auditLogService->log('admin.category_created', $request->user(), $category, ['code' => $category->code], null, $request);

        return response()->json(['data' => $category], 201);
    }

    public function storeSubcategory(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', 'exists:workspace_categories,id'],
            'code' => ['required', 'string', 'max:50', 'unique:workspace_subcategories,code'],
            'name' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:500'],
            'price_unit_label' => ['nullable', 'string', 'max:100'],
            'supports_upcoming' => ['sometimes', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $subcategory = WorkspaceSubcategory::query()->create(array_merge($validated, [
            'code' => strtoupper($validated['code']),
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]));

        $this->auditLogService->log('admin.subcategory_created', $request->user(), $subcategory, ['code' => $subcategory->code], null, $request);

        return response()->json(['data' => $subcategory], 201);
    }

    public function amenities(): JsonResponse
    {
        return response()->json(['data' => Amenity::query()->orderBy('sort_order')->get()]);
    }

    public function storeAmenity(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'slug' => ['nullable', 'string', 'max:120', 'unique:amenities,slug'],
            'icon_key' => ['nullable', 'string', 'max:60'],
            'category' => ['nullable', 'string', 'max:60'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $amenity = Amenity::query()->create([
            'name' => $validated['name'],
            'slug' => $validated['slug'] ?? Str::slug($validated['name']),
            'icon_key' => $validated['icon_key'] ?? null,
            'category' => $validated['category'] ?? null,
            'sort_order' => $validated['sort_order'] ?? 0,
            'is_active' => (bool) ($validated['is_active'] ?? true),
        ]);

        $this->auditLogService->log('admin.amenity_created', $request->user(), $amenity, [], null, $request);

        return response()->json(['data' => $amenity], 201);
    }

    public function updateAmenity(Amenity $amenity, Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:100'],
            'icon_key' => ['sometimes', 'nullable', 'string', 'max:60'],
            'category' => ['sometimes', 'nullable', 'string', 'max:60'],
            'sort_order' => ['sometimes', 'integer', 'min:0'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $amenity->forceFill($validated)->save();

        return response()->json(['data' => $amenity]);
    }
}
