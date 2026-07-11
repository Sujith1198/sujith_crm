<?php

namespace App\Services;

use App\Jobs\PublishScheduledPostJob;
use App\Models\Post;
use App\Models\PostMedia;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Repositories\Contracts\PostRepositoryInterface;
use App\Services\ActivityLogService;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

/**
 * PostService
 * Handles post creation, scheduling, publishing, and media management.
 */
class PostService
{
    public function __construct(
        protected PostRepositoryInterface $postRepository,
        protected ActivityLogService $activityLogService,
    ) {}

    public function paginate(array $filters, bool $isAdmin = false): mixed
    {
        $userId = $isAdmin ? null : auth()->id();
        return $this->postRepository->paginateWithFilters($filters, $userId);
    }

    public function find(int $id): Post
    {
        return $this->postRepository->with(['media', 'user', 'scheduledPosts'])->findOrFail($id);
    }

    /**
     * Create a new post with media and schedule it if a publish date is provided.
     */
    public function create(array $data, array $mediaFiles = []): Post
    {
        return DB::transaction(function () use ($data, $mediaFiles) {
            $post = $this->postRepository->create([
                'user_id'           => auth()->id(),
                'title'             => $data['title'],
                'caption'           => $data['caption'] ?? null,
                'description'       => $data['description'] ?? null,
                'hashtags'          => $data['hashtags'] ?? null,
                'post_type'         => $data['post_type'] ?? 'text',
                'status'            => empty($data['publish_at']) ? 'draft' : 'scheduled',
                'publish_at'        => $data['publish_at'] ?? null,
                'timezone'          => $data['timezone'] ?? 'UTC',
                'post_to_facebook'  => $data['platforms']['facebook'] ?? false,
                'post_to_instagram' => $data['platforms']['instagram'] ?? false,
            ]);

            // Handle media uploads
            if (! empty($mediaFiles)) {
                $this->attachMedia($post, $mediaFiles);
            }

            // Create scheduled post records and dispatch jobs
            if ($post->status === 'scheduled') {
                $this->schedulePost($post, $data);
            }

            $this->activityLogService->logModel(
                action: 'post.created',
                description: "Post '{$post->title}' created",
                model: $post,
                newValues: $post->toArray(),
            );

            return $post->load(['media', 'scheduledPosts']);
        });
    }

    public function update(int $id, array $data, array $mediaFiles = []): Post
    {
        return DB::transaction(function () use ($id, $data, $mediaFiles) {
            $post = $this->postRepository->findOrFail($id);
            $oldValues = $post->toArray();

            // Only allow editing unpublished posts
            if ($post->isPublished()) {
                throw new \Exception('Cannot edit a published post.', 422);
            }

            $updateData = array_filter([
                'title'             => $data['title'] ?? null,
                'caption'           => $data['caption'] ?? null,
                'description'       => $data['description'] ?? null,
                'hashtags'          => $data['hashtags'] ?? null,
                'post_type'         => $data['post_type'] ?? null,
                'publish_at'        => $data['publish_at'] ?? null,
                'timezone'          => $data['timezone'] ?? null,
                'post_to_facebook'  => $data['platforms']['facebook'] ?? null,
                'post_to_instagram' => $data['platforms']['instagram'] ?? null,
            ], fn ($v) => $v !== null);

            // Recalculate status
            if (isset($data['publish_at'])) {
                $updateData['status'] = empty($data['publish_at']) ? 'draft' : 'scheduled';
            }

            $post = $this->postRepository->update($id, $updateData);

            if (! empty($mediaFiles)) {
                $this->attachMedia($post, $mediaFiles);
            }

            // Re-schedule if publish time changed
            if (isset($data['publish_at']) && $post->status === 'scheduled') {
                ScheduledPost::where('post_id', $id)->where('status', 'pending')->delete();
                $this->schedulePost($post, $data);
            }

            $this->activityLogService->logModel(
                action: 'post.updated',
                description: "Post '{$post->title}' updated",
                model: $post,
                oldValues: $oldValues,
                newValues: $post->fresh()->toArray(),
            );

            return $post->load(['media', 'scheduledPosts']);
        });
    }

    public function delete(int $id): void
    {
        $post = $this->postRepository->findOrFail($id);

        if ($post->isPublished()) {
            throw new \Exception('Cannot delete a published post.', 422);
        }

        // Cancel pending schedule records
        ScheduledPost::where('post_id', $id)->where('status', 'pending')->update(['status' => 'cancelled']);

        $this->activityLogService->logModel(
            action: 'post.deleted',
            description: "Post '{$post->title}' deleted",
            model: $post,
        );

        $this->postRepository->delete($id);
    }

    // ──────────────────────────────────────────────
    // Media Handling
    // ──────────────────────────────────────────────

    protected function attachMedia(Post $post, array $files): void
    {
        foreach ($files as $index => $file) {
            /** @var UploadedFile $file */
            $type     = str_starts_with($file->getMimeType(), 'video/') ? 'video' : 'image';
            $path     = $file->store("posts/{$post->id}", 'public');
            $fileName = $file->getClientOriginalName();

            PostMedia::create([
                'post_id'    => $post->id,
                'type'       => $type,
                'file_path'  => $path,
                'file_name'  => $fileName,
                'mime_type'  => $file->getMimeType(),
                'file_size'  => $file->getSize(),
                'disk'       => 'public',
                'sort_order' => $index,
            ]);
        }
    }

    public function deleteMedia(int $mediaId): void
    {
        $media = PostMedia::findOrFail($mediaId);
        Storage::disk($media->disk)->delete($media->file_path);
        if ($media->thumbnail_path) {
            Storage::disk($media->disk)->delete($media->thumbnail_path);
        }
        $media->delete();
    }

    // ──────────────────────────────────────────────
    // Scheduling
    // ──────────────────────────────────────────────

    protected function schedulePost(Post $post, array $data): void
    {
        $accounts = SocialAccount::where('user_id', $post->user_id)->where('is_active', true)->get();

        foreach ($accounts as $account) {
            $shouldPost = ($account->platform === 'facebook' && $post->post_to_facebook)
                       || ($account->platform === 'instagram' && $post->post_to_instagram);

            if (! $shouldPost) continue;

            $scheduledPost = ScheduledPost::create([
                'post_id'           => $post->id,
                'user_id'           => $post->user_id,
                'social_account_id' => $account->id,
                'platform'          => $account->platform,
                'scheduled_at'      => $post->publish_at,
                'status'            => 'pending',
            ]);

            // Dispatch job at the right time
            PublishScheduledPostJob::dispatch($scheduledPost->id)
                ->delay($post->publish_at);
        }
    }

    public function getDashboardStats(): array
    {
        return $this->postRepository->getDashboardStats();
    }
}
