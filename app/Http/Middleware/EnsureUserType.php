<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserType
{
    public function handle(Request $request, Closure $next, string ...$allowedTypes): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Unauthenticated or inactive account.',
                ], 401);
            }

            $loginRoute = match (true) {
                $request->is('provider', 'provider/*') || in_array('provider', $allowedTypes, true) => 'provider.login',
                $request->is('admin', 'admin/*') || in_array('super_admin', $allowedTypes, true) => 'admin.login',
                default => 'login',
            };

            return redirect()->guest(route($loginRoute))
                ->withErrors(['identifier' => 'Please sign in to an active account to continue.']);
        }

        if (! in_array($user->user_type, $allowedTypes, true)) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json([
                    'message' => 'Forbidden for this account portal.',
                ], 403);
            }

            abort(403, 'Forbidden for this account portal.');
        }

        return $next($request);
    }
}
