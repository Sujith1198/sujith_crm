<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use PHPOpenSourceSaver\JWTAuth\Facades\JWTAuth;

/**
 * AuthService
 * Handles login, logout, token refresh, and password reset flows.
 */
class AuthService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected ActivityLogService $activityLogService,
    ) {}

    /**
     * Attempt login and return JWT token + user data.
     *
     * @throws \Illuminate\Validation\UnauthorizedException
     */
    public function login(array $credentials, string $ip): array
    {
        $user = $this->userRepository->findByEmail($credentials['email']);

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw new \Exception('Invalid credentials.', 401);
        }

        if (! $user->isActive()) {
            throw new \Exception('Account is disabled. Please contact an administrator.', 403);
        }

        $token = JWTAuth::fromUser($user);

        // Update last login
        $this->userRepository->updateLastLogin($user->id, $ip);

        // Log activity
        $this->activityLogService->log(
            action: 'auth.login',
            description: "User {$user->email} logged in",
            userId: $user->id,
            ip: $ip,
        );

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60, // seconds
            'user'       => $user->load('roles.permissions'),
        ];
    }

    /**
     * Invalidate current JWT token.
     */
    public function logout(): void
    {
        $user = auth()->user();
        JWTAuth::invalidate(JWTAuth::getToken());

        $this->activityLogService->log(
            action: 'auth.logout',
            description: "User {$user->email} logged out",
            userId: $user->id,
        );
    }

    /**
     * Refresh the current JWT token.
     */
    public function refresh(): array
    {
        $token = JWTAuth::refresh(JWTAuth::getToken());

        return [
            'token'      => $token,
            'token_type' => 'Bearer',
            'expires_in' => config('jwt.ttl') * 60,
        ];
    }

    /**
     * Return the authenticated user with roles & permissions.
     */
    public function me(): User
    {
        return auth()->user()->load('roles.permissions');
    }
}
