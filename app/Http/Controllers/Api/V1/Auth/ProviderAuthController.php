<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\ProviderRegisterRequest;
use App\Http\Requests\Auth\ResendActivationRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use App\Http\Resources\ProviderResource;
use App\Http\Resources\UserResource;
use App\Models\ActivationToken;
use App\Models\ProviderUser;
use App\Models\User;
use App\Services\Auth\AuthService;
use App\Services\RedirectSafetyService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProviderAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService,
        private readonly RedirectSafetyService $redirectSafety
    ) {}

    public function registerProvider(ProviderRegisterRequest $request): JsonResponse
    {
        $result = $this->authService->registerProvider($request->validated());

        return response()->json([
            'message' => 'Partner account created! Please verify your email to activate your account.',
            'data' => [
                'user' => new UserResource($result['user']),
                'provider' => new ProviderResource($result['provider']),
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
            User::TYPE_PROVIDER
        );

        /** @var User $user */
        $user = $result['user'];
        $memberships = $user->providerMemberships()
            ->with(['provider', 'providerRole'])
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->get();

        return response()->json([
            'message' => 'Signed in successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'providers' => ProviderResource::collection($memberships->pluck('provider')->filter()->unique('id')->values()),
                'redirect_to' => $this->redirectSafety->sanitize($validated['redirect_to'] ?? null, '/provider/dashboard'),
            ],
        ]);
    }

    public function activateProvider(VerifyEmailRequest $request): JsonResponse
    {
        $user = $this->authService->activateByToken($request->validated('token'), ActivationToken::TYPE_PROVIDER);

        return response()->json([
            'message' => 'Provider account activated successfully.',
            'data' => [
                'user' => new UserResource($user),
            ],
        ]);
    }

    public function resendProviderActivation(ResendActivationRequest $request): JsonResponse
    {
        $this->authService->resendActivation($request->validated('email'), ActivationToken::TYPE_PROVIDER);

        return response()->json([
            'message' => 'If an unactivated partner account exists for that email, an activation email has been resent.',
        ]);
    }

    public function roleContext(Request $request): JsonResponse
    {
        /** @var User $user */
        $user = $request->user();
        $providerId = $request->query('providerId');

        $memberships = $user->providerMemberships()
            ->with(['provider', 'providerRole'])
            ->where('status', ProviderUser::STATUS_ACTIVE)
            ->when($providerId, fn ($q) => $q->where('provider_id', (int) $providerId))
            ->get();

        if ($providerId && $memberships->isEmpty()) {
            return response()->json(['message' => 'Forbidden: no access to requested provider.'], 403);
        }

        $primary = $memberships->sortByDesc('is_owner')->first();

        return response()->json([
            'data' => [
                'user' => new UserResource($user),
                'provider' => $primary?->provider ? new ProviderResource($primary->provider) : null,
                'role' => $primary?->role_name,
                'is_owner' => (bool) ($primary?->is_owner ?? false),
                'permissions' => $primary?->is_owner
                    ? \App\Models\ProviderRole::CANONICAL_PERMISSIONS
                    : ($primary?->providerRole?->permissions ?? []),
            ],
        ]);
    }
}
