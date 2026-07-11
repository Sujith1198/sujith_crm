<?php

namespace App\Repositories\Contracts;

use App\Models\User;
use Illuminate\Pagination\LengthAwarePaginator;

interface UserRepositoryInterface extends BaseRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function paginateWithFilters(array $filters): LengthAwarePaginator;

    public function updateLastLogin(int $userId, string $ip): void;

    public function getUsersByRole(string $role): mixed;
}
