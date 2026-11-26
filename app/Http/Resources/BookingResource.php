<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'booking_reference' => $this->booking_reference,
            'booking_type' => $this->booking_type,
            'workspace_id' => $this->workspace_id,
            'provider_id' => $this->provider_id,
            'booking_date' => $this->booking_date?->toDateString(),
            'slot' => $this->slot,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'seat_count' => $this->seat_count,
            'unit_price' => (float) $this->unit_price,
            'subtotal_amount' => (float) $this->subtotal_amount,
            'coupon_code' => $this->coupon_code,
            'discount_amount' => (float) $this->discount_amount,
            'taxable_amount' => (float) $this->taxable_amount,
            'tax_rate' => $this->tax_rate !== null ? (float) $this->tax_rate : null,
            'tax_amount' => (float) $this->tax_amount,
            'total_amount' => (float) $this->total_amount,
            'currency' => $this->currency,
            'status' => $this->status,
            'payment_status' => $this->payment_status,
            'hold_expires_at' => $this->hold_expires_at?->toIso8601String(),
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
                'slug' => $this->workspace->slug,
                'address_line' => $this->workspace->address_line,
            ]),
            'items' => $this->whenLoaded('items', fn () => $this->items->map(fn ($item) => [
                'id' => $item->id,
                'item_label' => $item->item_label,
                'quantity' => $item->quantity,
                'unit_price' => (float) $item->unit_price,
                'line_total' => (float) $item->line_total,
            ])),
            'payments' => $this->whenLoaded('paymentTransactions', fn () => PaymentTransactionResource::collection($this->paymentTransactions)),
            'cancellation_record' => $this->whenLoaded('cancellationRecord', fn () => $this->cancellationRecord ? [
                'reason' => $this->cancellationRecord->reason,
                'refundable_amount' => (float) $this->cancellationRecord->refundable_amount,
                'cancelled_at' => $this->cancellationRecord->cancelled_at?->toIso8601String(),
            ] : null),
        ];
    }
}
