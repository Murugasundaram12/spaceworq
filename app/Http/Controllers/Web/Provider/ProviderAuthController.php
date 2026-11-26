<?php

namespace App\Http\Controllers\Web\Provider;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Web\ResendWebActivationRequest;
use App\Http\Requests\Web\WebProviderRegisterRequest;
use App\Models\ActivationToken;
use App\Models\ProviderUser;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\AuthService;
use App\Services\RedirectSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ProviderAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectSafetyService $redirectSafety,
        private readonly AuditLogService $auditLogService
    ) {}

    public function showLogin(Request $request): View|Response
    {
        return $this->renderPortalView('vendor.auth.login', [
            'redirectTo' => $this->redirectSafety->sanitize($request->query('redirectTo'), route('provider.dashboard', [], false)),
        ]);
    }

    public function showRegister(): View|Response
    {
        return $this->renderPortalView('vendor.auth.register');
    }

    public function showForgotPassword(): View|Response
    {
        return $this->renderPortalView('vendor.auth.forgot-password');
    }

    public function showResetPassword(Request $request, string $token): View|Response
    {
        return $this->renderPortalView('vendor.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function register(WebProviderRegisterRequest $request): JsonResponse|RedirectResponse
    {
        $result = $this->authService->registerProvider($request->validated());
        $message = 'Partner account created! Please verify your email to activate your account.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'email' => $result['user']->email,
                'requires_activation' => true,
            ], 201);
        }

        return redirect()->route('provider.login')->with('status', $message);
    }

    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = $this->authService->verifySessionLogin(
                $validated['identifier'],
                $validated['password'],
                User::TYPE_PROVIDER,
                true
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

        $primaryMembership = $user->providerMemberships()
            ->with('provider')
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->orderByDesc('is_owner')
            ->first();

        $defaultRoute = $primaryMembership?->provider?->isVerifiedAndActive()
            ? route('provider.dashboard', [], false)
            : route('provider.profile.show', [], false);

        $redirectTo = $primaryMembership?->provider?->isVerifiedAndActive()
            ? $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, $defaultRoute)
            : $defaultRoute;

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Signed in successfully.',
                'redirect_to' => $redirectTo,
            ]);
        }

        return redirect()->to($redirectTo);
    }

    public function activate(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:16', 'max:128'],
        ]);

        $this->authService->activateByToken($validated['token'], ActivationToken::TYPE_PROVIDER);
        $message = 'Provider account activated successfully. Please sign in to complete your Company Profile & KYC.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('provider.login')->with('status', $message);
    }

    public function resendActivation(ResendWebActivationRequest $request): JsonResponse|RedirectResponse
    {
        $this->authService->resendActivation($request->validated('email'), ActivationToken::TYPE_PROVIDER);
        $message = 'If an unactivated partner account exists for that email, an activation email has been resent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function sendResetLink(ResendWebActivationRequest $request): JsonResponse|RedirectResponse
    {
        Password::broker()->sendResetLink([
            'email' => strtolower(trim($request->validated('email'))),
        ]);

        $message = 'If an account exists for that email, a password reset link has been sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse|RedirectResponse
    {
        $email = strtolower(trim($request->validated('email')));
        $target = User::query()->where('email', $email)->first();

        if ($target && $target->user_type !== User::TYPE_PROVIDER) {
            throw ValidationException::withMessages([
                'email' => ['Invalid partner password reset request.'],
            ]);
        }

        $status = Password::broker()->reset(
            array_merge($request->only('password', 'password_confirmation', 'token'), ['email' => $email]),
            function (User $user, string $password): void {
                $user->forceFill([
                    'password' => $password,
                    'remember_token' => Str::random(60),
                ])->save();

                $user->tokens()->delete();
                $this->auditLogService->log('auth.password_reset', $user, $user);
            }
        );

        if ($status !== Password::PASSWORD_RESET) {
            if ($request->expectsJson()) {
                return response()->json(['message' => __($status)], 422);
            }

            return back()->withErrors(['email' => [__($status)]])->withInput(['email' => $email]);
        }

        $message = 'Password reset successfully. You may now sign in.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('provider.login')->with('status', $message);
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

        return redirect()->route('provider.login');
    }
}
