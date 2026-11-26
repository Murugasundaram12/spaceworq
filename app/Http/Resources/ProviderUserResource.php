<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class ProviderUserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'provider_id' => $this->provider_id,
            'user_id' => $this->user_id,
            'workspace_id' => $this->workspace_id,
            'provider_role_id' => $this->provider_role_id,
            'role_name' => $this->role_name,
            'is_owner' => $this->is_owner,
            'status' => $this->status,
            'user' => $this->whenLoaded('user', fn () => [
                'id' => $this->user->id,
                'name' => $this->user->name,
                'email' => $this->user->email,
                'mobile' => $this->user->mobile,
            ]),
            'role' => $this->whenLoaded('providerRole', fn () => $this->providerRole ? [
                'id' => $this->providerRole->id,
                'name' => $this->providerRole->name,
                'permissions' => $this->providerRole->permissions ?? [],
            ] : null),
            'workspace' => $this->whenLoaded('workspace', fn () => $this->workspace ? [
                'id' => $this->workspace->id,
                'code' => $this->workspace->code,
                'name' => $this->workspace->name,
            ] : null),
        ];
    }
}
