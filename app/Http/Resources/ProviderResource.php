<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_name' => $this->company_name,
            'slug' => $this->slug,
            'brand_name' => $this->brand_name,
            'business_email' => $this->business_email,
            'business_phone' => $this->business_phone,
            'city_id' => $this->city_id,
            'address' => $this->address,
            'status' => $this->status,
            'status_label' => $this->resource->statusLabel(),
            'kyc_status' => $this->kyc_status,
            'kyc_status_label' => $this->resource->kycStatusLabel(),
            'status_reason' => $this->status_reason,
            'submitted_at' => $this->submitted_at?->toIso8601String(),
            'status_updated_at' => $this->status_updated_at?->toIso8601String(),
            'verified_at' => $this->verified_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
