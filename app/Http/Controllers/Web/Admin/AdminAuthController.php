<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\AuthService;
use App\Services\RedirectSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectSafetyService $redirectSafety,
        private readonly AuditLogService $auditLogService
    ) {}

    public function showLogin(Request $request): View|Response
    {
        return $this->renderPortalView('admin.auth.login', [
            'redirectTo' => $this->redirectSafety->sanitize($request->query('redirectTo'), route('admin.dashboard', [], false)),
        ]);
    }

    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = $this->authService->verifySessionLogin(
                $validated['identifier'],
                $validated['password'],
                User::TYPE_SUPER_ADMIN
            );
        } catch (HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
            }

            return back()
                ->withErrors(['identifier' => $e->getMessage()])
                ->withInput($request->only('identifier', 'remember'));
        }

        Auth::guard('web')->login($user, (bool) ($validated['remember'] ?? false));
        $request->session()->regenerate();

        $redirectTo = $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, route('admin.dashboard', [], false));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Super Admin signed in successfully.',
                'redirect_to' => $redirectTo,
            ]);
        }

        return redirect()->to($redirectTo);
    }

    public function logout(Request $request): JsonResponse|RedirectResponse
    {
        /** @var User|null $user */
        $user = $request->user();
        if ($user) {
            $this->auditLogService->log('auth.web_logout', $user, $user, ['guard' => 'web'], null, $request);
        }

        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        if ($request->expectsJson()) {
            return response()->json(['message' => 'Signed out successfully.']);
        }

        return redirect()->route('admin.login');
    }
}
