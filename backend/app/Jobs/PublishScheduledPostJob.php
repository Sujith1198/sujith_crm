<?php

namespace App\Jobs;

use App\Models\Post;
use App\Models\ScheduledPost;
use App\Models\SocialAccount;
use App\Services\FacebookService;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * PublishScheduledPostJob
 *
 * Dispatched when a post's publish_at time arrives.
 * Publishes the post to the configured platforms (Facebook and/or Instagram).
 * On failure: marks status as 'failed', increments retry_count.
 * On success: marks status as 'published', stores platform post IDs.
 */
class PublishScheduledPostJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maximum attempts before marking as permanently failed */
    public int $tries = 3;

    /** Delay between retries in seconds */
    public int $backoff = 300; // 5 minutes

    /** Timeout per attempt */
    public int $timeout = 120;

    public function __construct(public readonly int $scheduledPostId) {}

    public function handle(FacebookService $facebookService, InstagramService $instagramService): void
    {
        /** @var ScheduledPost $scheduledPost */
        $scheduledPost = ScheduledPost::with(['post.media', 'socialAccount'])->find($this->scheduledPostId);

        if (! $scheduledPost) {
            Log::warning("PublishScheduledPostJob: ScheduledPost #{$this->scheduledPostId} not found.");
            return;
        }

        // Skip if already processed or cancelled
        if (in_array($scheduledPost->status, ['published', 'cancelled', 'failed'])) {
            return;
        }

        $post    = $scheduledPost->post;
        $account = $scheduledPost->socialAccount;

        // Mark as processing
        $scheduledPost->update([
            'status'            => 'processing',
            'attempts'          => $scheduledPost->attempts + 1,
            'last_attempted_at' => now(),
        ]);

        $post->update(['status' => 'publishing']);

        try {
            $platformPostId = match ($scheduledPost->platform) {
                'facebook'  => $this->publishToFacebook($post, $account, $facebookService),
                'instagram' => $this->publishToInstagram($post, $account, $instagramService),
                default     => throw new \Exception("Unknown platform: {$scheduledPost->platform}"),
            };

            // Success — update both records
            $scheduledPost->update([
                'status'            => 'published',
                'platform_post_id'  => $platformPostId,
                'published_at'      => now(),
            ]);

            // Update the parent Post with platform-specific ID
            if ($scheduledPost->platform === 'facebook') {
                $post->update(['facebook_post_id' => $platformPostId]);
            } elseif ($scheduledPost->platform === 'instagram') {
                $post->update(['instagram_media_id' => $platformPostId]);
            }

            // If all scheduled posts for this post are done, mark post as published
            $allDone = $post->scheduledPosts()
                ->whereNotIn('status', ['published', 'cancelled'])
                ->doesntExist();

            if ($allDone) {
                $post->update(['status' => 'published']);
            }

            Log::info("Post #{$post->id} published to {$scheduledPost->platform}. Platform ID: {$platformPostId}");

        } catch (\Exception $e) {
            Log::error("PublishScheduledPostJob failed for ScheduledPost #{$scheduledPost->id}", [
                'error'    => $e->getMessage(),
                'attempts' => $scheduledPost->attempts,
            ]);

            $scheduledPost->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            $post->update([
                'status'        => 'failed',
                'error_message' => $e->getMessage(),
                'retry_count'   => $post->retry_count + 1,
                'last_retry_at' => now(),
            ]);

            // Re-throw so Laravel's retry mechanism can handle it
            throw $e;
        }
    }

    // ──────────────────────────────────────────────
    // Platform-specific publishing
    // ──────────────────────────────────────────────

    protected function publishToFacebook(Post $post, SocialAccount $account, FacebookService $fb): string
    {
        $caption = $this->buildCaption($post);
        $media   = $post->media;

        if ($media->isEmpty() || $post->post_type === 'text') {
            $result = $fb->createTextPost($account, $caption);
            return $result['id'];
        }

        if ($post->post_type === 'image' && $media->count() === 1) {
            $imageUrl = asset('storage/' . $media->first()->file_path);
            $result   = $fb->createImagePost($account, $caption, $imageUrl);
            return $result['id'] ?? $result['post_id'];
        }

        if ($post->post_type === 'carousel') {
            $imageUrls = $media->pluck('file_path')
                ->map(fn ($p) => asset('storage/' . $p))
                ->toArray();
            $result = $fb->createCarouselPost($account, $caption, $imageUrls);
            return $result['id'];
        }

        if ($post->post_type === 'video') {
            $videoPath = Storage::disk('public')->path($media->first()->file_path);
            $result    = $fb->createVideoPost($account, $caption, $videoPath);
            return $result['id'];
        }

        // Fallback: text post
        $result = $fb->createTextPost($account, $caption);
        return $result['id'];
    }

    protected function publishToInstagram(Post $post, SocialAccount $account, InstagramService $ig): string
    {
        $caption = $this->buildCaption($post);
        $media   = $post->media;

        if ($post->post_type === 'image' && $media->count() === 1) {
            $imageUrl = asset('storage/' . $media->first()->file_path);
            $result   = $ig->createImagePost($account, $caption, $imageUrl);
            return $result['id'];
        }

        if ($post->post_type === 'carousel') {
            $imageUrls = $media->pluck('file_path')
                ->map(fn ($p) => asset('storage/' . $p))
                ->toArray();
            $result = $ig->createCarouselPost($account, $caption, $imageUrls);
            return $result['id'];
        }

        if (in_array($post->post_type, ['video', 'reel'])) {
            $videoUrl = asset('storage/' . $media->first()->file_path);
            $isReel   = $post->post_type === 'reel';
            $result   = $ig->createVideoPost($account, $caption, $videoUrl, $isReel);
            return $result['id'];
        }

        // Fallback: image post if any media exists
        if ($media->isNotEmpty()) {
            $imageUrl = asset('storage/' . $media->first()->file_path);
            $result   = $ig->createImagePost($account, $caption, $imageUrl);
            return $result['id'];
        }

        throw new \Exception('Instagram requires at least one media file.');
    }

    protected function buildCaption(Post $post): string
    {
        $parts = array_filter([$post->caption, $post->hashtags]);
        return implode("\n\n", $parts);
    }

    /**
     * Handle final job failure (after all retries exhausted).
     */
    public function failed(\Throwable $exception): void
    {
        Log::critical("PublishScheduledPostJob permanently failed for ScheduledPost #{$this->scheduledPostId}", [
            'error' => $exception->getMessage(),
        ]);

        ScheduledPost::where('id', $this->scheduledPostId)->update([
            'status'        => 'failed',
            'error_message' => 'Max retries reached: ' . $exception->getMessage(),
        ]);
    }
}
