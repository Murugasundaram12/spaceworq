<?php

namespace App\Policies;

use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\User;

class ProviderPolicy
{
    public function view(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return $this->isActiveMember($user, $provider);
    }

    public function update(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        return ProviderUser::query()
            ->where('provider_id', $provider->id)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->where('is_owner', true)
            ->exists();
    }

    public function manageUsers(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->membership($user, $provider);
        return $membership !== null && $membership->hasPermission('providers.users.view');
    }

    public function manageRoles(User $user, Provider $provider): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = $this->membership($user, $provider);
        return $membership !== null && $membership->hasPermission('providers.roleManagement.view');
    }

    private function isActiveMember(User $user, Provider $provider): bool
    {
        return $this->membership($user, $provider) !== null;
    }

    private function membership(User $user, Provider $provider): ?ProviderUser
    {
        return ProviderUser::query()
            ->with('providerRole')
            ->where('provider_id', $provider->id)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->first();
    }
}
