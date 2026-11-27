<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\ChangePasswordRequest;
use App\Http\Requests\Auth\CustomerRegisterRequest;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ResendActivationRequest;
use App\Http\Requests\Auth\ResetPasswordRequest;
use App\Http\Requests\Auth\SendOtpRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Requests\Auth\VerifyOtpRequest;
use App\Http\Resources\UserResource;
use App\Models\ActivationToken;
use App\Models\User;
use App\Services\AuditLogService;
use App\Services\Auth\AuthService;
use App\Services\RedirectSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectSafetyService $redirectSafety,
        private readonly AuditLogService $auditLogService
    ) {}

    public function register(CustomerRegisterRequest $request): JsonResponse
    {
        $user = $this->authService->registerCustomer($request->validated(), false);

        return response()->json([
            'message' => 'Account created successfully. Please check your email to activate your account.',
            'data' => [
                'user' => new UserResource($user),
                'requires_activation' => true,
            ],
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->attemptLogin(
            $validated['identifier'],
            $validated['password'],
            null
        );

        return response()->json([
            'message' => 'Signed in successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'redirect_to' => $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, '/'),
            ],
        ]);
    }

    public function customerLogin(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->attemptLogin(
            $validated['identifier'],
            $validated['password'],
            User::TYPE_CUSTOMER
        );

        return response()->json([
            'message' => 'Signed in successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'redirect_to' => $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, '/'),
            ],
        ]);
    }

    public function verifyEmail(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->authService->activateByToken($request->validated('token'), ActivationToken::TYPE_CLIENT);

        return response()->json([
            'message' => 'Account activated successfully. You may now sign in.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function resendClientActivation(ResendActivationRequest $request): JsonResponse
    {
        $this->authService->resendActivation($request->validated('email'), ActivationToken::TYPE_CLIENT);

        return response()->json([
            'message' => 'If an unactivated account exists for that email, an activation link has been sent.',
        ]);
    }

    public function sendOtp(SendOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $this->authService->sendOtp($validated['identifier'], $validated['purpose'] ?? 'login');

        return response()->json([
            'message' => 'If an active account matches that contact, a 6-digit verification code has been sent.',
        ]);
    }

    public function verifyOtp(VerifyOtpRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->verifyOtp(
            $validated['identifier'],
            $validated['otp'],
            $validated['purpose'] ?? 'login'
        );

        return response()->json([
            'message' => 'OTP verified successfully.',
            'data' => [
                'user' => new UserResource($result['user']),
                'token' => $result['token'],
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function forgotPassword(ResendActivationRequest $request): JsonResponse
    {
        Password::broker()->sendResetLink(['email' => strtolower(trim($request->validated('email')))]);

        return response()->json([
            'message' => 'If an account exists for that email, a password reset link has been sent.',
        ]);
    }

    public function resetPassword(ResetPasswordRequest $request): JsonResponse
    {
        $status = Password::broker()->reset(
            $request->only('email', 'password', 'password_confirmation', 'token'),
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
            return response()->json(['message' => __($status)], 422);
        }

        return response()->json(['message' => 'Password reset successfully.']);
    }

    public function changePassword(ChangePasswordRequest $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $validated = $request->validated();

        if (! Hash::check($validated['current_password'], $user->password)) {
            return response()->json([
                'message' => 'Current password is incorrect.',
                'errors' => ['current_password' => ['Current password is incorrect.']],
            ], 422);
        }

        $user->forceFill(['password' => $validated['password']])->save();
        $currentTokenId = $user->currentAccessToken()?->id;
        $user->tokens()->when($currentTokenId, fn ($q) => $q->where('id', '!=', $currentTokenId))->delete();

        $this->auditLogService->log('auth.password_changed', $user, $user);

        return response()->json(['message' => 'Password changed successfully.']);
    }

    public function refresh(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();
        $token = $user->createToken($user->user_type . '-refreshed-token')->plainTextToken;

        return response()->json([
            'message' => 'Token refreshed.',
            'data' => [
                'token' => $token,
                'token_type' => 'Bearer',
            ],
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $user->currentAccessToken()?->delete();

        $this->auditLogService->log('auth.logout', $user, $user);

        return response()->json(['message' => 'Signed out successfully.']);
    }

    public function me(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user()->loadMissing('customerProfile');

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'roles' => $user->getRoleNames()->values(),
            ],
        ]);
    }
}
