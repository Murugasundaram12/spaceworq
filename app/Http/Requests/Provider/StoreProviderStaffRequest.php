<?php

namespace App\Http\Requests\Provider;

use Illuminate\Foundation\Http\FormRequest;

class StoreProviderStaffRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'first_name' => ['required', 'string', 'max:80'],
            'last_name' => ['nullable', 'string', 'max:80'],
            'email' => ['required', 'email', 'max:150'],
            'mobile' => ['nullable', 'string', 'max:25'],
            'password' => ['nullable', 'string', 'min:8', 'max:100'],
            'provider_role_id' => ['required', 'integer', 'exists:provider_roles,id'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
        ];
    }
}
