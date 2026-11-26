<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class CreateReserveRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'requested_seats' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'move_in_date' => ['nullable', 'date', 'after_or_equal:today'],
            'duration_months' => ['nullable', 'integer', 'min:1', 'max:120'],
            'request_summary' => ['nullable', 'string', 'max:1500'],
        ];
    }
}
