<?php

namespace App\Http\Controllers\Api\V1\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Services\Auth\AuthService;
use Illuminate\Http\JsonResponse;

class AdminAuthController extends Controller
{
    public function __construct(
        private readonly AuthService $authService
    ) {}

    public function login(LoginRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $result = $this->authService->attemptLogin(
            $validated['identifier'],
            $validated['password'],
            User::TYPE_SUPER_ADMIN
        );

        /** @var User $user */
        $user = $result['user'];
        if (! $user->hasRole('super_admin')) {
            return response()->json(['message' => 'Super Admin role is required.'], 403);
        }

        return response()->json([
            'message' => 'Super Admin signed in successfully.',
            'data' => [
                'user' => new UserResource($user),
                'token' => $result['token'],
                'token_type' => 'Bearer',
                'permissions' => $user->getAllPermissions()->pluck('name')->values(),
            ],
        ]);
    }
}
