<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\Password;

class ProviderRegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'first_name' => $this->input('first_name', $this->input('firstName')),
            'last_name' => $this->input('last_name', $this->input('lastName')),
            'password_confirmation' => $this->input('password_confirmation', $this->input('confirm_password')),
        ]);
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'min:1', 'max:100'],
            'last_name' => ['required', 'string', 'min:1', 'max:100'],
            'email' => ['required', 'string', 'email:rfc', 'max:180', 'unique:users,email'],
            'mobile' => ['required', 'string', 'regex:/^[\d\s\-()+]{7,20}$/', 'unique:users,mobile'],
            'password' => ['required', 'confirmed', Password::min(8)],
            'company_name' => ['nullable', 'string', 'max:180'],
            'brand_name' => ['nullable', 'string', 'max:180'],
            'city_id' => ['nullable', 'integer', 'exists:cities,id'],
            'address' => ['nullable', 'string', 'max:500'],
        ];
    }
}
