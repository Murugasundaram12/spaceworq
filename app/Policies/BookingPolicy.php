<?php

namespace App\Policies;

use App\Models\Booking;
use App\Models\ProviderUser;
use App\Models\User;

class BookingPolicy
{
    public function viewCustomerBooking(User $user, Booking $booking): bool
    {
        return $booking->customer_user_id === $user->id || $user->isSuperAdmin();
    }

    public function manageAsCustomer(User $user, Booking $booking): bool
    {
        return $booking->customer_user_id === $user->id;
    }

    public function manageAsProvider(User $user, Booking $booking): bool
    {
        if ($user->isSuperAdmin()) {
            return true;
        }

        $membership = ProviderUser::query()
            ->with('providerRole')
            ->where('provider_id', $booking->provider_id)
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->first();

        return $membership !== null && $membership->hasPermission('providers.instantBookingRequests.view');
    }
}
