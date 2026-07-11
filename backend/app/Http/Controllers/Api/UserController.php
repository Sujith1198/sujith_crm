<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\User\CreateUserRequest;
use App\Http\Requests\User\UpdateUserRequest;
use App\Http\Resources\UserResource;
use App\Services\UserService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * UserController
 * Full CRUD for user management (Admin only).
 */
class UserController extends Controller
{
    public function __construct(protected UserService $userService)
    {
        $this->middleware('auth:api');
        $this->middleware('role:admin')->except(['show', 'updateProfile']);
    }

    public function index(Request $request): JsonResponse
    {
        $users = $this->userService->paginate($request->only([
            'search', 'status', 'role', 'sort_by', 'sort_dir', 'per_page',
        ]));

        return response()->json([
            'success' => true,
            'data'    => UserResource::collection($users->items()),
            'meta'    => [
                'total'        => $users->total(),
                'per_page'     => $users->perPage(),
                'current_page' => $users->currentPage(),
                'last_page'    => $users->lastPage(),
            ],
        ]);
    }

    public function show(int $id): JsonResponse
    {
        // Users can only see their own profile unless admin
        if (! auth()->user()->isAdmin() && auth()->id() !== $id) {
            return response()->json(['success' => false, 'message' => 'Forbidden.'], 403);
        }

        return response()->json([
            'success' => true,
            'data'    => new UserResource($this->userService->find($id)),
        ]);
    }

    public function store(CreateUserRequest $request): JsonResponse
    {
        try {
            $user = $this->userService->create($request->validated());
            return response()->json([
                'success' => true,
                'message' => 'User created successfully.',
                'data'    => new UserResource($user),
            ], 201);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function update(UpdateUserRequest $request, int $id): JsonResponse
    {
        try {
            $user = $this->userService->update($id, $request->validated());
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
                'data'    => new UserResource($user),
            ]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function destroy(int $id): JsonResponse
    {
        try {
            $this->userService->delete($id);
            return response()->json(['success' => true, 'message' => 'User deleted successfully.']);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], $e->getCode() ?: 422);
        }
    }

    public function toggleStatus(int $id): JsonResponse
    {
        $user = $this->userService->toggleStatus($id);
        return response()->json([
            'success' => true,
            'message' => "User status changed to {$user->status}.",
            'data'    => new UserResource($user),
        ]);
    }

    public function resetPassword(Request $request, int $id): JsonResponse
    {
        $request->validate(['password' => 'required|string|min:8|confirmed']);
        $this->userService->resetPassword($id, $request->password);
        return response()->json(['success' => true, 'message' => 'Password reset successfully.']);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $request->validate([
            'name'     => 'sometimes|string|max:255',
            'phone'    => 'sometimes|nullable|string|max:20',
            'timezone' => 'sometimes|string|max:50',
            'password' => 'sometimes|string|min:8|confirmed',
        ]);

        $user = $this->userService->update(auth()->id(), $request->all());
        return response()->json(['success' => true, 'data' => new UserResource($user)]);
    }
}
