<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if (! $this->filled('identifier') && $this->filled('email')) {
            $this->merge(['identifier' => $this->input('email')]);
        }
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:180'],
            'password' => ['required', 'string', 'min:6', 'max:255'],
            'remember' => ['nullable', 'boolean'],
            'redirect_to' => ['nullable', 'string', 'max:500'],
        ];
    }
}
