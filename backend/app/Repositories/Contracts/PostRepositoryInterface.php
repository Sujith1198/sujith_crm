<?php

namespace App\Repositories\Contracts;

use App\Models\Post;
use Illuminate\Pagination\LengthAwarePaginator;

interface PostRepositoryInterface extends BaseRepositoryInterface
{
    public function paginateWithFilters(array $filters, ?int $userId = null): LengthAwarePaginator;

    public function getDueScheduledPosts(): mixed;

    public function getStatsByUser(?int $userId = null): array;

    public function getDashboardStats(): array;
}
