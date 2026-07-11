<?php

namespace App\Services;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

/**
 * UserService
 * Handles all business logic for user management: create, update, delete,
 * role assignment, password resets, and status management.
 */
class UserService
{
    public function __construct(
        protected UserRepositoryInterface $userRepository,
        protected ActivityLogService $activityLogService,
    ) {}

    public function paginate(array $filters): mixed
    {
        return $this->userRepository->paginateWithFilters($filters);
    }

    public function find(int $id): User
    {
        return $this->userRepository->with(['roles', 'socialAccounts'])->findOrFail($id);
    }

    public function create(array $data): User
    {
        $user = $this->userRepository->create([
            'name'     => $data['name'],
            'email'    => $data['email'],
            'phone'    => $data['phone'] ?? null,
            'password' => Hash::make($data['password']),
            'status'   => $data['status'] ?? 'active',
            'timezone' => $data['timezone'] ?? 'UTC',
        ]);

        // Assign role
        if (! empty($data['role'])) {
            $user->assignRole($data['role']);
        } else {
            $user->assignRole('user');
        }

        $this->activityLogService->logModel(
            action: 'user.created',
            description: "User {$user->email} created",
            model: $user,
            newValues: $user->toArray(),
        );

        return $user->load('roles');
    }

    public function update(int $id, array $data): User
    {
        $user = $this->userRepository->findOrFail($id);
        $oldValues = $user->toArray();

        $updateData = array_filter([
            'name'     => $data['name'] ?? null,
            'phone'    => $data['phone'] ?? null,
            'status'   => $data['status'] ?? null,
            'timezone' => $data['timezone'] ?? null,
        ]);

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user = $this->userRepository->update($id, $updateData);

        // Update role
        if (! empty($data['role'])) {
            $user->syncRoles([$data['role']]);
        }

        $this->activityLogService->logModel(
            action: 'user.updated',
            description: "User {$user->email} updated",
            model: $user,
            oldValues: $oldValues,
            newValues: $user->fresh()->toArray(),
        );

        return $user->load('roles');
    }

    public function delete(int $id): void
    {
        $user = $this->userRepository->findOrFail($id);

        // Prevent deleting system admin
        if ($user->isAdmin() && User::role('admin')->count() === 1) {
            throw new \Exception('Cannot delete the last admin account.', 422);
        }

        $this->activityLogService->logModel(
            action: 'user.deleted',
            description: "User {$user->email} deleted",
            model: $user,
        );

        $this->userRepository->delete($id);
    }

    public function toggleStatus(int $id): User
    {
        $user = $this->userRepository->findOrFail($id);
        $newStatus = $user->status === 'active' ? 'inactive' : 'active';
        return $this->userRepository->update($id, ['status' => $newStatus]);
    }

    public function resetPassword(int $id, string $newPassword): void
    {
        $this->userRepository->update($id, ['password' => Hash::make($newPassword)]);
    }

    public function sendPasswordResetEmail(string $email): void
    {
        Password::sendResetLink(['email' => $email]);
    }
}
