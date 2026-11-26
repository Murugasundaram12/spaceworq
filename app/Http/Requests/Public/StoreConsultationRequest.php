<?php

namespace App\Http\Requests\Public;

use Illuminate\Foundation\Http\FormRequest;

class StoreConsultationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:150'],
            'company_name' => ['required', 'string', 'min:2', 'max:180'],
            'phone' => ['required', 'string', 'regex:/^[\d\s\-()+]{7,25}$/'],
            'email' => ['nullable', 'string', 'email:rfc', 'max:180'],
            'city_name' => ['required', 'string', 'max:120'],
            'category_interest' => ['nullable', 'string', 'max:120'],
            'seats_range' => ['nullable', 'string', 'max:60'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
