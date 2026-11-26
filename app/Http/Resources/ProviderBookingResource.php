<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderBookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'workspace_id' => $this->workspace_id,
            'assigned_provider_user_id' => $this->assigned_provider_user_id,
            'booking_date' => $this->booking_date?->toDateString(),
            'slot' => $this->slot,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'seat_count' => $this->seat_count,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'contact_name' => $this->contact_name,
            'contact_email' => $this->contact_email,
            'contact_phone' => $this->contact_phone,
            'remarks' => $this->remarks,
            'completed_at' => $this->completed_at?->toIso8601String(),
            'cancelled_at' => $this->cancelled_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'workspace' => $this->whenLoaded('workspace', fn () => [
                'id' => $this->workspace->id,
                'code' => $this->workspace->code,
                'name' => $this->workspace->name,
            ]),
            'assigned_staff' => $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff ? [
                'id' => $this->assignedStaff->id,
                'name' => $this->assignedStaff->user?->name,
            ] : null),
        ];
    }
}
