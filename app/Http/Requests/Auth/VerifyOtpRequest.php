<?php

namespace App\Http\Requests\Auth;

use Illuminate\Foundation\Http\FormRequest;

class VerifyOtpRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'identifier' => ['required', 'string', 'max:180'],
            'otp' => ['required', 'string', 'regex:/^\d{6}$/'],
            'purpose' => ['nullable', 'string', 'in:login,activation'],
        ];
    }
}
