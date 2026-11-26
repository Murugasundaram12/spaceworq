<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CheckAvailabilityRequest extends FormRequest
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
        ];
    }
}
