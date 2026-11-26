<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->name,
            'generated_post_title' => $this->generated_post_title,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'code' => $this->category->code,
                'name' => $this->category->name,
            ]),
            'subcategory' => $this->whenLoaded('subcategory', fn () => [
                'id' => $this->subcategory->id,
                'code' => $this->subcategory->code,
                'name' => $this->subcategory->name,
            ]),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ]),
            'locality' => $this->whenLoaded('locality', fn () => $this->locality ? [
                'id' => $this->locality->id,
                'name' => $this->locality->name,
            ] : null),
            'address_line' => $this->address_line,
            'landmark' => $this->landmark,
            'pincode' => $this->pincode,
            'description_html' => $this->description_html,
            'total_floors' => $this->total_floors,
            'total_seats' => $this->total_seats,
            'available_seats' => $this->available_seats,
            'is_upcoming' => $this->is_upcoming,
            'tentative_availability_date' => $this->tentative_availability_date?->toDateString(),
            'gst_enabled' => $this->gst_enabled,
            'tax_rate' => $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'status' => $this->status,
            'approval_status' => $this->approval_status,
            'is_published' => $this->is_published,
            'is_instant_bookable' => $this->is_instant_bookable,
            'approved_at' => $this->approved_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'listing_configs' => $this->whenLoaded('listingConfigs'),
            'working_hours' => $this->whenLoaded('workingHours'),
            'amenities' => $this->whenLoaded('amenities', fn () => $this->amenities->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
            ])),
            'floors' => $this->whenLoaded('floors', fn () => $this->floors->map(fn ($f) => [
                'id' => $f->id,
                'floor_number' => $f->floor_number,
                'label' => $f->label,
                'total_seats' => $f->total_seats,
                'is_active' => $f->is_active,
                'template_mapping' => $f->relationLoaded('templateMapping') && $f->templateMapping ? [
                    'id' => $f->templateMapping->id,
                    'layout_template_id' => $f->templateMapping->layout_template_id,
                ] : null,
            ])),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'original_filename' => $img->original_filename,
                'mime_type' => $img->mime_type,
                'size_bytes' => $img->size_bytes,
                'sort_order' => $img->sort_order,
                'is_primary' => $img->is_primary,
                'url' => "/api/v1/workspaces/published/{$this->id}/images/{$img->id}/file",
            ])),
        ];
    }
}
