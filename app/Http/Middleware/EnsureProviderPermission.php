<?php

namespace App\Http\Middleware;

use App\Models\ProviderUser;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProviderPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        /** @var ProviderUser|null $providerUser */
        $providerUser = $request->attributes->get('current_provider_user');

        if (!$providerUser || !$providerUser->hasPermission($permission)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => "Forbidden: missing provider permission [{$permission}].",
                ], 403);
            }

            abort(403, "Forbidden: missing provider permission [{$permission}].");
        }

        return $next($request);
    }
}
