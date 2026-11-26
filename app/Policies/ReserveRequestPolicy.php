<?php

namespace App\Policies;

use App\Models\ProviderUser;
use App\Models\ReserveRequest;
use App\Models\User;

class ReserveRequestPolicy
{
    public function viewAsCustomer(User $user, ReserveRequest $reserveRequest): bool
    {
        return $reserveRequest->customer_user_id === $user->id || $user->isSuperAdmin();
    }

    public function manageAsCustomer(User $user, ReserveRequest $reserveRequest): bool
    {
        return $reserveRequest->customer_user_id === $user->id;
    }

    public function manageAsProvider(User $user, ReserveRequest $reserveRequest): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = ProviderUser::query()
            ->with('providerRole')
            ->where('provider_id', $reserveRequest->provider_id)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->first();

        return $membership !== null && $membership->hasPermission('providers.reserveRequests.view');
    }
}
