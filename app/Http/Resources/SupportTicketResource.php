<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class SupportTicketResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'ticket_code' => $this->ticket_code,
            'user_id' => $this->user_id,
            'booking_id' => $this->booking_id,
            'category' => $this->category,
            'subject' => $this->subject,
            'message' => $this->message,
            'status' => $this->status,
            'resolution_notes' => $this->resolution_notes,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
