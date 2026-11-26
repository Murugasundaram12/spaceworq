<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;

class UpdateWebProviderProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('business_email')) {
            $this->merge(['business_email' => strtolower(trim((string) $this->input('business_email')))]);
        }
        if ($this->filled('business_phone')) {
            $this->merge(['business_phone' => trim((string) $this->input('business_phone'))]);
        }
        if ($this->filled('gstin')) {
            $this->merge(['gstin' => strtoupper(trim((string) $this->input('gstin')))]);
        }
        if ($this->filled('pan')) {
            $this->merge(['pan' => strtoupper(trim((string) $this->input('pan')))]);
        }
    }

    public function rules(): array
    {
        return [
            'company_name' => ['required', 'string', 'min:2', 'max:180'],
            'brand_name' => ['nullable', 'string', 'max:180'],
            'business_email' => ['required', 'string', 'email:rfc', 'max:180'],
            'business_phone' => ['required', 'string', 'regex:/^[\d\s\-()+]{7,25}$/'],
            'gstin' => ['nullable', 'string', 'max:30'],
            'pan' => ['nullable', 'string', 'max:20'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['required', 'string', 'min:5', 'max:500'],
            'resubmit_application' => ['sometimes', 'boolean'],
            'settings' => ['sometimes', 'array'],
            'settings.auto_accept_instant_bookings' => ['sometimes', 'boolean'],
            'settings.notification_email' => ['sometimes', 'nullable', 'email:rfc', 'max:150'],
            'settings.gst_registered' => ['sometimes', 'boolean'],
            'settings.default_tax_rate' => ['sometimes', 'nullable', 'numeric', 'min:0', 'max:50'],
            'settings.timezone' => ['sometimes', 'string', 'max:60'],
        ];
    }
}
