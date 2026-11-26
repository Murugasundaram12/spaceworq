<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;

class PublicWorkspaceDetailResource extends PublicWorkspaceResource
{
    public function toArray(Request $request): array
    {
        $base = parent::toArray($request);

        return array_merge($base, [
            'description_html' => $this->description_html,
            'working_hours' => $this->whenLoaded('workingHours', fn () => $this->workingHours->map(fn ($wh) => [
                'day_of_week' => $wh->day_of_week,
                'day_name' => $wh->day_name,
                'open_time' => $wh->open_time,
                'close_time' => $wh->close_time,
                'is_closed' => $wh->is_closed,
            ])->values()),
            'listing_configs' => $this->whenLoaded('listingConfigs', fn () => $this->listingConfigs->where('is_active', true)->map(fn ($cfg) => [
                'id' => $cfg->id,
                'subcategory_id' => $cfg->subcategory_id,
                'price_per_day' => $cfg->price_per_day !== null ? (float) $cfg->price_per_day : null,
                'price_per_half_day' => $cfg->price_per_half_day !== null ? (float) $cfg->price_per_half_day : null,
                'price_per_month' => $cfg->price_per_month !== null ? (float) $cfg->price_per_month : null,
                'price_per_hour' => $cfg->price_per_hour !== null ? (float) $cfg->price_per_hour : null,
                'price_per_sqft' => $cfg->price_per_sqft !== null ? (float) $cfg->price_per_sqft : null,
                'price_per_year' => $cfg->price_per_year !== null ? (float) $cfg->price_per_year : null,
                'min_seats' => $cfg->min_seats,
                'max_seats' => $cfg->max_seats,
            ])->values()),
        ]);
    }
}
