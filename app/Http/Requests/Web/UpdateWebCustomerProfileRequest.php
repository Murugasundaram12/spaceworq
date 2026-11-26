<?php

namespace App\Http\Requests\Web;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateWebCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        if ($this->has('mobile') && $this->input('mobile') !== null) {
            $this->merge(['mobile' => trim((string) $this->input('mobile'))]);
        }
    }

    public function rules(): array
    {
        $userId = $this->user()?->id;

        return [
            'first_name' => ['required', 'string', 'min:2', 'max:80'],
            'last_name' => ['required', 'string', 'min:1', 'max:80'],
            'mobile' => [
                'nullable',
                'string',
                'regex:/^\d{10}$/',
                Rule::unique('users', 'mobile')->ignore($userId),
            ],
            'company_name' => ['nullable', 'string', 'max:150'],
            'gstin' => ['nullable', 'string', 'max:30'],
            'preferred_city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'billing_address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
