<?php

namespace App\Http\Controllers\Web\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Requests\Web\ResendWebActivationRequest;
use App\Http\Requests\Web\UpdateWebCustomerProfileRequest;
use App\Http\Requests\Web\WebCustomerRegisterRequest;
use App\Models\ActivationToken;
use App\Models\City;
use App\Models\CustomerProfile;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\AuthService;
use App\Services\RedirectSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpKernel\Exception\HttpException;

class CustomerAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectSafetyService $redirectSafety,
        private readonly AuditLogService $auditLogService
    ) {}

    public function showLogin(Request $request): View|Response
    {
        return $this->renderPortalView('customer.auth.login', [
            'redirectTo' => $this->redirectSafety->sanitize($request->query('redirectTo'), '/'),
        ]);
    }

    public function showRegister(Request $request): View|Response
    {
        return $this->renderPortalView('customer.auth.register', [
            'redirectTo' => $this->redirectSafety->sanitize(
                $request->query('redirectTo') ?? $request->query('redirect_to'),
                route('home', [], false)
            ),
        ]);
    }

    public function showForgotPassword(): View|Response
    {
        return $this->renderPortalView('customer.auth.forgot-password');
    }

    public function showResetPassword(Request $request, string $token): View|Response
    {
        return $this->renderPortalView('customer.auth.reset-password', [
            'token' => $token,
            'email' => (string) $request->query('email', ''),
        ]);
    }

    public function register(WebCustomerRegisterRequest $request): JsonResponse|RedirectResponse
    {
        $user = $this->authService->registerCustomer($request->validated(), true);

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $defaultDestination = route('home', [], false);
        $rawRedirect = $request->input('redirect_to') ?? $request->input('redirectTo');
        $redirectTo = ($rawRedirect && $rawRedirect !== '/')
            ? $this->redirectSafety->sanitize($rawRedirect, $defaultDestination)
            : $defaultDestination;

        $message = 'Account registered and activated successfully.';

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'email' => $user->email,
                'requires_activation' => false,
                'redirect_to' => $redirectTo,
            ], 201);
        }

        return redirect()->to($redirectTo)->with('status', $message);
    }

    public function login(LoginRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = $this->authService->verifySessionLogin(
                $validated['identifier'],
                $validated['password'],
                User::TYPE_CUSTOMER
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

        $redirectTo = $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, route('home', [], false));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Signed in successfully.',
                'redirect_to' => $redirectTo,
            ]);
        }

        return redirect()->to($redirectTo);
    }

    public function verifyEmail(Request $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'token' => ['required', 'string', 'min:16', 'max:128'],
        ]);

        $this->authService->activateByToken($validated['token'], ActivationToken::TYPE_CLIENT);
        $message = 'Account activated successfully. You may now sign in.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return redirect()->route('login')->with('status', $message);
    }

    public function resendActivation(ResendWebActivationRequest $request): JsonResponse|RedirectResponse
    {
        $this->authService->resendActivation($request->validated('email'), ActivationToken::TYPE_CLIENT);
        $message = 'If an unactivated account exists for that email, an activation link has been sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();
        $this->authService->sendOtp($validated['identifier'], $validated['purpose'] ?? 'login');
        $message = 'If an active account matches that contact, a 6-digit verification code has been sent.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse|RedirectResponse
    {
        $validated = $request->validated();

        try {
            $user = $this->authService->verifyOtpSession(
                $validated['identifier'],
                $validated['otp'],
                $validated['purpose'] ?? 'login'
            );
        } catch (HttpException $e) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $e->getMessage()], $e->getStatusCode());
            }

            return back()->withErrors(['otp' => $e->getMessage()]);
        }

        Auth::guard('web')->login($user);
        $request->session()->regenerate();

        $redirectTo = $this->redirectSafety->sanitize($request->input('redirect_to'), route('customer.profile.show', [], false));

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'OTP verified successfully.',
                'redirect_to' => $redirectTo,
            ]);
        }

        return redirect()->to($redirectTo);
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

        if ($target && $target->user_type !== User::TYPE_CUSTOMER) {
            throw ValidationException::withMessages([
                'email' => ['Invalid customer password reset request.'],
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

        return redirect()->route('login')->with('status', $message);
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

        return redirect()->route('home');
    }

    public function profile(Request $request): View|Response
    {
        /** @var User $user */
        $user = $request->user()->load('customerProfile.preferredCity');

        return $this->renderPortalView('customer.profile', [
            'user' => $user,
            'profile' => $user->customerProfile,
            'cities' => City::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function updateProfile(UpdateWebCustomerProfileRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        DB::transaction(function () use ($user, $validated): void {
            $firstName = trim((string) $validated['first_name']);
            $lastName = trim((string) $validated['last_name']);

            $user->forceFill([
                'first_name' => $firstName,
                'last_name' => $lastName,
                'name' => trim("{$firstName} {$lastName}"),
                'mobile' => $validated['mobile'] ?? $user->mobile,
            ])->save();

            CustomerProfile::query()->updateOrCreate(
                ['user_id' => $user->id],
                [
                    'company_name' => $validated['company_name'] ?? null,
                    'gstin' => $validated['gstin'] ?? null,
                    'preferred_city_id' => $validated['preferred_city_id'] ?? null,
                    'billing_address' => $validated['billing_address'] ?? null,
                ]
            );
        });

        $this->auditLogService->log('customer.profile_updated', $user, $user, [], null, $request);
        $message = 'Customer profile updated successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse|RedirectResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        if (! Hash::check($validated['current_password'], $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['Current password is incorrect.'],
            ]);
        }

        $user->forceFill(['password' => $validated['password']])->save();
        $this->auditLogService->log('auth.password_changed', $user, $user, ['guard' => 'web'], null, $request);
        $message = 'Password changed successfully.';

        if ($request->expectsJson()) {
            return response()->json(['message' => $message]);
        }

        return back()->with('status', $message);
    }
}
