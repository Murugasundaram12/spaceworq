<?php

namespace App\Http\Requests\Provider;

use App\Models\ProviderRole;
use Illuminate\Foundation\Http\FormRequest;

class StoreProviderRoleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:100'],
            'description' => ['nullable', 'string', 'max:500'],
            'workspace_id' => ['nullable', 'integer', 'exists:workspaces,id'],
            'permissions' => ['required', 'array', 'min:1'],
            'permissions.*' => ['string', 'in:' . implode(',', ProviderRole::CANONICAL_PERMISSIONS)],
        ];
    }
}
