<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PublicWorkspaceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $configs = $this->whenLoaded('listingConfigs', fn () => $this->listingConfigs->where('is_active', true)->values(), collect());
        $primaryConfig = $configs->firstWhere('subcategory_id', $this->subcategory_id) ?? $configs->first();

        return [
            'id' => $this->id,
            'code' => $this->code,
            'slug' => $this->slug,
            'name' => $this->name,
            'generated_post_title' => $this->generated_post_title,
            'category' => $this->whenLoaded('category', fn () => [
                'id' => $this->category->id,
                'code' => $this->category->code,
                'name' => $this->category->name,
                'booking_mode' => $this->category->default_booking_mode,
            ]),
            'subcategory' => $this->whenLoaded('subcategory', fn () => [
                'id' => $this->subcategory->id,
                'code' => $this->subcategory->code,
                'name' => $this->subcategory->name,
                'price_unit_label' => $this->subcategory->price_unit_label,
            ]),
            'city' => $this->whenLoaded('city', fn () => [
                'id' => $this->city->id,
                'name' => $this->city->name,
                'slug' => $this->city->slug,
            ]),
            'locality' => $this->whenLoaded('locality', fn () => $this->locality ? [
                'id' => $this->locality->id,
                'name' => $this->locality->name,
                'slug' => $this->locality->slug,
            ] : null),
            'address_line' => $this->address_line,
            'landmark' => $this->landmark,
            'pincode' => $this->pincode,
            'latitude' => $this->latitude !== null ? (float) $this->latitude : null,
            'longitude' => $this->longitude !== null ? (float) $this->longitude : null,
            'total_floors' => $this->total_floors,
            'total_seats' => $this->total_seats,
            'available_seats' => $this->available_seats,
            'availability_badge' => $this->available_seats > 5 ? 'available' : ($this->available_seats > 0 ? 'filling' : 'unavailable'),
            'is_upcoming' => $this->is_upcoming,
            'tentative_availability_date' => $this->tentative_availability_date?->toDateString(),
            'is_instant_bookable' => $this->is_instant_bookable,
            'rating' => (float) $this->rating_avg,
            'reviews_count' => $this->reviews_count,
            'gst_enabled' => $this->gst_enabled,
            'tax_rate' => $this->gst_enabled && $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'pricing' => $primaryConfig ? [
                'price_per_day' => $primaryConfig->price_per_day !== null ? (float) $primaryConfig->price_per_day : null,
                'price_per_half_day' => $primaryConfig->price_per_half_day !== null ? (float) $primaryConfig->price_per_half_day : null,
                'price_per_month' => $primaryConfig->price_per_month !== null ? (float) $primaryConfig->price_per_month : null,
                'price_per_hour' => $primaryConfig->price_per_hour !== null ? (float) $primaryConfig->price_per_hour : null,
                'price_per_sqft' => $primaryConfig->price_per_sqft !== null ? (float) $primaryConfig->price_per_sqft : null,
                'price_per_year' => $primaryConfig->price_per_year !== null ? (float) $primaryConfig->price_per_year : null,
                'min_seats' => $primaryConfig->min_seats,
                'max_seats' => $primaryConfig->max_seats,
            ] : null,
            'amenities' => $this->whenLoaded('amenities', fn () => $this->amenities->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->name,
                'slug' => $a->slug,
                'icon_key' => $a->icon_key,
            ])->values()),
            'images' => $this->whenLoaded('images', fn () => $this->images->map(fn ($img) => [
                'id' => $img->id,
                'is_primary' => $img->is_primary,
                'sort_order' => $img->sort_order,
                'url' => "/api/v1/workspaces/published/{$this->id}/images/{$img->id}/file",
            ])->values()),
            'provider' => $this->whenLoaded('provider', fn () => [
                'id' => $this->provider->id,
                'brand_name' => $this->provider->brand_name ?? $this->provider->company_name,
                'slug' => $this->provider->slug,
            ]),
        ];
    }
}
