<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Resources\UserResource;
use App\Services\AuthService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AuthController
 * Handles JWT-based authentication endpoints.
 */
class AuthController extends Controller
{
    public function __construct(protected AuthService $authService)
    {
        $this->middleware('auth:api')->except(['login']);
    }

    /**
     * @OA\Post(path="/api/login", ...)
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            $result = $this->authService->login(
                credentials: $request->only('email', 'password'),
                ip: $request->ip(),
            );

            return response()->json([
                'success' => true,
                'message' => 'Login successful.',
                'data'    => [
                    'token'      => $result['token'],
                    'token_type' => $result['token_type'],
                    'expires_in' => $result['expires_in'],
                    'user'       => new UserResource($result['user']),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], $e->getCode() ?: 401);
        }
    }

    public function logout(): JsonResponse
    {
        try {
            $this->authService->logout();
            return response()->json(['success' => true, 'message' => 'Logged out successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function refresh(): JsonResponse
    {
        try {
            $result = $this->authService->refresh();
            return response()->json(['success' => true, 'data' => $result]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 401);
        }
    }

    public function me(): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data'    => new UserResource($this->authService->me()),
        ]);
    }
}
