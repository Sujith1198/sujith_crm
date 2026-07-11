<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Contracts\UserRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;

class UserRepository extends BaseRepository implements UserRepositoryInterface
{
    public function __construct(User $model)
    {
        parent::__construct($model);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->model->where('email', $email)->first();
    }

    public function paginateWithFilters(array $filters): LengthAwarePaginator
    {
        $query = $this->model->with('roles');

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['role'])) {
            $query->whereHas('roles', fn ($q) => $q->where('name', $filters['role']));
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDir   = $filters['sort_dir'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        $allowedSorts = ['name', 'email', 'status', 'created_at', 'last_login_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function updateLastLogin(int $userId, string $ip): void
    {
        $this->model->where('id', $userId)->update([
            'last_login_at' => now(),
            'last_login_ip' => $ip,
        ]);
    }

    public function getUsersByRole(string $role): mixed
    {
        return $this->model->whereHas('roles', fn ($q) => $q->where('name', $role))->get();
    }
}
