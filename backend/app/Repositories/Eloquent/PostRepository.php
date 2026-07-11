<?php

namespace App\Repositories\Eloquent;

use App\Models\Post;
use App\Repositories\Contracts\PostRepositoryInterface;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class PostRepository extends BaseRepository implements PostRepositoryInterface
{
    public function __construct(Post $model)
    {
        parent::__construct($model);
    }

    public function paginateWithFilters(array $filters, ?int $userId = null): LengthAwarePaginator
    {
        $query = $this->model->with(['user', 'media']);

        // Scope to user's own posts unless admin
        if ($userId) {
            $query->where('user_id', $userId);
        }

        if (! empty($filters['search'])) {
            $query->search($filters['search']);
        }

        if (! empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (! empty($filters['platform'])) {
            $platform = $filters['platform'];
            if ($platform === 'facebook') $query->where('post_to_facebook', true);
            if ($platform === 'instagram') $query->where('post_to_instagram', true);
        }

        if (! empty($filters['post_type'])) {
            $query->where('post_type', $filters['post_type']);
        }

        if (! empty($filters['date_from'])) {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if (! empty($filters['date_to'])) {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $sortField = $filters['sort_by'] ?? 'created_at';
        $sortDir   = $filters['sort_dir'] ?? 'desc';
        $perPage   = $filters['per_page'] ?? 15;

        $allowedSorts = ['title', 'status', 'post_type', 'publish_at', 'created_at'];
        if (in_array($sortField, $allowedSorts)) {
            $query->orderBy($sortField, $sortDir === 'asc' ? 'asc' : 'desc');
        }

        return $query->paginate($perPage);
    }

    public function getDueScheduledPosts(): mixed
    {
        return $this->model
            ->with(['scheduledPosts.socialAccount', 'media'])
            ->where('status', 'scheduled')
            ->where('publish_at', '<=', now())
            ->get();
    }

    public function getStatsByUser(?int $userId = null): array
    {
        $query = $this->model->when($userId, fn ($q) => $q->where('user_id', $userId));

        return [
            'total'      => (clone $query)->count(),
            'draft'      => (clone $query)->where('status', 'draft')->count(),
            'scheduled'  => (clone $query)->where('status', 'scheduled')->count(),
            'published'  => (clone $query)->where('status', 'published')->count(),
            'failed'     => (clone $query)->where('status', 'failed')->count(),
            'cancelled'  => (clone $query)->where('status', 'cancelled')->count(),
        ];
    }

    public function getDashboardStats(): array
    {
        return [
            'total_posts'     => $this->model->count(),
            'published_posts' => $this->model->where('status', 'published')->count(),
            'scheduled_posts' => $this->model->where('status', 'scheduled')->count(),
            'draft_posts'     => $this->model->where('status', 'draft')->count(),
        ];
    }
}
