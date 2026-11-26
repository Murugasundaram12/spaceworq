<?php

namespace App\Http\Requests\Provider;

use App\Models\Workspace;
use Illuminate\Foundation\Http\FormRequest;

class UpdateWorkspaceRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'required', 'string', 'max:180'],
            'generated_post_title' => ['sometimes', 'nullable', 'string', 'max:220'],
            'category_id' => ['sometimes', 'integer', 'exists:workspace_categories,id'],
            'subcategory_id' => ['sometimes', 'integer', 'exists:workspace_subcategories,id'],
            'city_id' => ['sometimes', 'integer', 'exists:cities,id'],
            'locality_id' => ['sometimes', 'nullable', 'integer', 'exists:localities,id'],
            'address_line' => ['sometimes', 'required', 'string', 'max:350'],
            'landmark' => ['sometimes', 'nullable', 'string', 'max:200'],
            'pincode' => ['sometimes', 'nullable', 'string', 'max:15'],
            'place_id' => ['sometimes', 'nullable', 'string', 'max:120'],
            'latitude' => ['sometimes', 'nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['sometimes', 'nullable', 'numeric', 'between:-180,180'],
            'description_html' => ['sometimes', 'nullable', 'string', 'max:10000'],
            'total_floors' => ['sometimes', 'integer', 'min:1', 'max:200'],
            'total_seats' => ['sometimes', 'integer', 'min:1', 'max:50000'],
            'available_seats' => ['sometimes', 'integer', 'min:0'],
            'is_upcoming' => ['sometimes', 'boolean'],
            'tentative_availability_date' => ['sometimes', 'nullable', 'date'],
            'gst_enabled' => ['sometimes', 'boolean'],
            'tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:50'],
            'is_instant_bookable' => ['sometimes', 'boolean'],
            'status' => ['sometimes', 'string', 'in:' . implode(',', [
                Workspace::STATUS_DRAFT,
                Workspace::STATUS_IN_REVIEW,
                Workspace::STATUS_ACTIVE,
                Workspace::STATUS_INACTIVE,
            ])],
            'amenity_ids' => ['sometimes', 'array'],
            'amenity_ids.*' => ['integer', 'exists:amenities,id'],
            'pricing' => ['sometimes', 'array'],
            'pricing.price_per_day' => ['nullable', 'numeric', 'min:0'],
            'pricing.price_per_month' => ['nullable', 'numeric', 'min:0'],
            'pricing.price_per_hour' => ['nullable', 'numeric', 'min:0'],
            'pricing.price_per_half_day' => ['nullable', 'numeric', 'min:0'],
            'pricing.price_per_sqft' => ['nullable', 'numeric', 'min:0'],
            'pricing.price_per_year' => ['nullable', 'numeric', 'min:0'],
            'pricing.min_seats' => ['nullable', 'integer', 'min:1'],
            'pricing.max_seats' => ['nullable', 'integer', 'min:1'],
            'working_hours' => ['sometimes', 'array', 'max:7'],
            'working_hours.*.day_of_week' => ['required', 'integer', 'between:0,6'],
            'working_hours.*.day_name' => ['nullable', 'string', 'max:20'],
            'working_hours.*.open_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.close_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/'],
            'working_hours.*.is_closed' => ['sometimes', 'boolean'],
        ];
    }
}
