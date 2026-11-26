<?php

namespace App\Http\Middleware;

use App\Models\Provider;
use App\Models\ProviderUser;
use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderAccess
{
    public function handle(Request $request, Closure $next, string $mode = 'false'): Response
    {
        $isApiOrJson = $request->is('api/*') || $request->expectsJson();
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            if ($isApiOrJson) {
                return response()->json(['message' => 'Unauthenticated or inactive account.'], 401);
            }

            return redirect()->guest(route('provider.login'));
        }

        if ($user->user_type !== User::TYPE_PROVIDER) {
            return $this->deny($isApiOrJson, 'Provider portal access denied.');
        }

        $requestedProviderId = $request->header('X-Provider-Id') ?? $request->input('provider_id');

        $membershipQuery = ProviderUser::query()
            ->with(['provider', 'providerRole'])
            ->where('user_id', $user->id)
            ->where('status', ProviderUser::STATUS_ACTIVE);

        if ($requestedProviderId !== null && $requestedProviderId !== '') {
            $membershipQuery->where('provider_id', (int) $requestedProviderId);
        }

        $membership = $membershipQuery->orderByDesc('is_owner')->first();

        if (! $membership || ! $membership->provider) {
            return $this->deny($isApiOrJson, 'Forbidden: active provider membership not found.');
        }

        $provider = $membership->provider;
        $request->attributes->set('current_provider', $provider);
        $request->attributes->set('current_provider_user', $membership);

        // Read-only status mode allows pending, rejected, suspended, and active provider members
        // to inspect application status and reason without unlocking operational routes.
        if ($mode === 'status') {
            return $next($request);
        }

        if ($provider->status === Provider::STATUS_SUSPENDED) {
            return $this->denyOrRedirectForStatus(
                $isApiOrJson,
                'Provider account is suspended. Onboarding updates and operational features are unavailable.'
            );
        }

        // 'false' preserves legacy API default behavior blocking REJECTED,
        // while 'onboarding' allows REJECTED owners to resubmit company profile & KYC on onboarding routes.
        if ($mode === 'false' && $provider->status === Provider::STATUS_REJECTED) {
            return $this->deny($isApiOrJson, 'Provider account is suspended or rejected.');
        }

        if ($mode === 'true' && ! $provider->isVerifiedAndActive()) {
            $message = $provider->status === Provider::STATUS_REJECTED
                ? 'Provider application was rejected. Please review notes and resubmit verification.'
                : 'Provider verification and KYC approval are required for this action.';

            if ($isApiOrJson) {
                return response()->json(['message' => $message], 403);
            }

            return redirect()->route('provider.profile.show')
                ->with('status_error', $message);
        }

        return $next($request);
    }

    private function deny(bool $isApiOrJson, string $message): Response
    {
        if ($isApiOrJson) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }

    private function denyOrRedirectForStatus(bool $isApiOrJson, string $message): Response
    {
        if ($isApiOrJson) {
            return response()->json(['message' => $message], 403);
        }

        abort(403, $message);
    }
}
