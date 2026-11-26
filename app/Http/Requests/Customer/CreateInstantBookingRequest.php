<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateInstantBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'booking_date' => ['required', 'date', 'after_or_equal:today'],
            'seat_count' => ['required', 'integer', 'min:1', 'max:500'],
            'slot' => ['nullable', 'string', 'in:morning,afternoon,fullday,custom'],
            'start_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'required_if:slot,custom'],
            'end_time' => ['nullable', 'regex:/^\d{2}:\d{2}(:\d{2})?$/', 'required_if:slot,custom'],
            'coupon_code' => ['nullable', 'string', 'max:60'],
            'idempotency_key' => ['nullable', 'string', 'max:100'],
            'contact_name' => ['nullable', 'string', 'max:120'],
            'contact_email' => ['nullable', 'email', 'max:150'],
            'contact_phone' => ['nullable', 'string', 'max:25'],
        ];
    }
}
