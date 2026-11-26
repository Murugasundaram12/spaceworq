<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderReserveRequestResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'request_code' => $this->request_code,
            'workspace_id' => $this->workspace_id,
            'assigned_provider_user_id' => $this->assigned_provider_user_id,
            'status' => $this->status,
            'requested_seats' => $this->requested_seats,
            'move_in_date' => $this->move_in_date?->toDateString(),
            'duration_months' => $this->duration_months,
            'request_summary' => $this->request_summary,
            'remarks' => $this->remarks,
            'reminders_count' => $this->reminders_count,
            'last_reminded_at' => $this->last_reminded_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
            'workspace' => $this->whenLoaded('workspace', fn () => [
                'id' => $this->workspace->id,
                'code' => $this->workspace->code,
                'name' => $this->workspace->name,
            ]),
            'customer' => $this->whenLoaded('customer', fn () => $this->customer ? [
                'id' => $this->customer->id,
                'name' => $this->customer->name,
                'email' => $this->customer->email,
                'mobile' => $this->customer->mobile,
            ] : null),
            'assigned_staff' => $this->whenLoaded('assignedStaff', fn () => $this->assignedStaff ? [
                'id' => $this->assignedStaff->id,
                'name' => $this->assignedStaff->user?->name,
            ] : null),
        ];
    }
}
