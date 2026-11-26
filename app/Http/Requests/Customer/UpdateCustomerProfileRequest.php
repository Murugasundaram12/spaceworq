<?php

namespace App\Http\Requests\Customer;

use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['sometimes', 'required', 'string', 'max:80'],
            'last_name' => ['sometimes', 'nullable', 'string', 'max:80'],
            'mobile' => ['sometimes', 'nullable', 'string', 'max:25'],
            'company_name' => ['sometimes', 'nullable', 'string', 'max:150'],
            'gstin' => ['sometimes', 'nullable', 'string', 'max:30'],
            'preferred_city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'billing_address' => ['sometimes', 'nullable', 'string', 'max:500'],
        ];
    }
}
