<?php

namespace App\Policies;

use App\Models\ProviderUser;
use App\Models\User;
use App\Models\Workspace;

class WorkspacePolicy
{
    public function view(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->membership($user, $workspace->provider_id);
        return $membership !== null && $membership->hasPermission('providers.workspaceManagement.view');
    }

    public function update(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function delete(User $user, Workspace $workspace): bool
    {
        return $this->view($user, $workspace);
    }

    public function manageFloors(User $user, Workspace $workspace): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->membership($user, $workspace->provider_id);
        return $membership !== null && $membership->hasPermission('providers.floors.view');
    }

    private function membership(User $user, int $providerId): ?ProviderUser
    {
        return ProviderUser::query()
            ->with('providerRole')
            ->where('provider_id', $providerId)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->first();
    }
}
