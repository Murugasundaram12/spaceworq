<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderBankAccountResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'account_holder_name' => $this->account_holder_name,
            'bank_name' => $this->bank_name,
            'masked_account_number' => $this->maskedAccountNumber(),
            'account_last4' => $this->account_last4,
            'ifsc_code' => $this->ifsc_code,
            'upi_id' => $this->upi_id,
            'is_primary' => $this->is_primary,
            'is_verified' => $this->is_verified,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
