<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'company_name' => ['sometimes', 'required', 'string', 'max:180'],
            'brand_name' => ['sometimes', 'nullable', 'string', 'max:180'],
            'business_email' => ['sometimes', 'required', 'email', 'max:150'],
            'business_phone' => ['sometimes', 'required', 'string', 'max:25'],
            'gstin' => ['sometimes', 'nullable', 'string', 'max:30'],
            'pan' => ['sometimes', 'nullable', 'string', 'max:20'],
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'address' => ['sometimes', 'nullable', 'string', 'max:400'],
            'settings' => ['sometimes', 'array'],
            'settings.auto_accept_instant_bookings' => ['sometimes', 'boolean'],
            'settings.notification_email' => ['sometimes', 'nullable', 'email', 'max:150'],
            'settings.gst_registered' => ['sometimes', 'boolean'],
            'settings.default_tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:50'],
            'settings.timezone' => ['sometimes', 'string', 'max:60'],
        ];
    }
}
