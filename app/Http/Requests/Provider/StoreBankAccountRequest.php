<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreBankAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'account_holder_name' => ['required', 'string', 'max:150'],
            'bank_name' => ['required', 'string', 'max:120'],
            'account_number' => ['required', 'string', 'min:6', 'max:30'],
            'ifsc_code' => ['required', 'string', 'max:20'],
            'upi_id' => ['nullable', 'string', 'max:100'],
            'is_primary' => ['sometimes', 'boolean'],
        ];
    }
}
