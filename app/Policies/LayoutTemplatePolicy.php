<?php

namespace App\Policies;

use App\Models\LayoutTemplate;
use App\Models\ProviderUser;
use App\Models\User;

class LayoutTemplatePolicy
{
    public function manage(User $user, LayoutTemplate $template): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = ProviderUser::query()
            ->with('providerRole')
            ->where('provider_id', $template->provider_id)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->first();

        return $membership !== null && $membership->hasPermission('providers.layoutDesigner.view');
    }
}
