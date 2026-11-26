<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureSuperAdmin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user || ! $user->isActive()) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Unauthenticated or inactive account.'], 401);
            }

            return redirect()->guest(route('admin.login'))
                ->withErrors(['identifier' => 'Super Admin sign in is required.']);
        }

        if ($user->user_type !== User::TYPE_SUPER_ADMIN || ! $user->hasRole('super_admin')) {
            if ($request->is('api/*') || $request->expectsJson()) {
                return response()->json(['message' => 'Super Admin access required.'], 403);
            }

            abort(403, 'Super Admin access required.');
        }

        return $next($request);
    }
}
