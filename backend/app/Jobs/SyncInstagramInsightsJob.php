<?php

namespace App\Jobs;

use App\Models\Analytics;
use App\Models\InstagramInsight;
use App\Models\SocialAccount;
use App\Services\InstagramService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncInstagramInsightsJob
 *
 * Dispatched by the scheduler every hour.
 * Fetches media and account insights from the Instagram Graph API.
 */
class SyncInstagramInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(public readonly int $socialAccountId) {}

    public function handle(InstagramService $instagramService): void
    {
        $account = SocialAccount::find($this->socialAccountId);

        if (! $account || ! $account->is_active || $account->platform !== 'instagram') {
            return;
        }

        if ($account->isTokenExpired()) {
            Log::warning("SyncInstagramInsightsJob: Token expired for account #{$account->id}");
            return;
        }

        try {
            $since = now()->subDay()->format('Y-m-d');
            $until = now()->format('Y-m-d');

            // Sync account-level insights
            $accountInsights = $instagramService->getAccountInsights($account, $since, $until);
            $this->upsertAccountInsights($account, $accountInsights);

            // Sync media-level insights for published posts
            $posts = $account->user->posts()
                ->where('post_to_instagram', true)
                ->where('status', 'published')
                ->whereNotNull('instagram_media_id')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            foreach ($posts as $post) {
                try {
                    $mediaInsights = $instagramService->getMediaInsights($account, $post->instagram_media_id);
                    $this->upsertMediaInsights($account, $post->id, $post->instagram_media_id, $mediaInsights);
                } catch (\Exception $e) {
                    Log::warning("Failed to sync IG insights for post #{$post->id}: " . $e->getMessage());
                }
            }

            // Update follower count
            $followers = $instagramService->getFollowerCount($account);
            $account->update([
                'followers_count' => $followers,
                'last_synced_at'  => now(),
            ]);

            Log::info("SyncInstagramInsightsJob: Completed for account #{$account->id}");

        } catch (\Exception $e) {
            Log::error("SyncInstagramInsightsJob failed for account #{$account->id}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function upsertAccountInsights(SocialAccount $account, array $response): void
    {
        if (empty($response['data'])) return;

        $date    = now()->subDay()->format('Y-m-d');
        $metrics = [];

        foreach ($response['data'] as $metric) {
            foreach ($metric['values'] as $value) {
                $metrics[$metric['name']] = $value['value'];
            }
        }

        InstagramInsight::updateOrCreate(
            [
                'social_account_id'  => $account->id,
                'instagram_media_id' => 'account_' . $account->account_id,
                'date'               => $date,
            ],
            [
                'post_id'               => null,
                'profile_views'         => $metrics['profile_views'] ?? 0,
                'website_clicks'        => $metrics['website_clicks'] ?? 0,
                'email_contacts'        => $metrics['email_contacts'] ?? 0,
                'follower_count'        => $account->followers_count,
                'follower_count_change' => $metrics['follower_count'] ?? 0,
                'raw_data'              => $response,
            ]
        );

        Analytics::updateOrCreate(
            ['social_account_id' => $account->id, 'post_id' => null, 'platform' => 'instagram', 'date' => $date],
            [
                'profile_visits'  => $metrics['profile_views'] ?? 0,
                'website_clicks'  => $metrics['website_clicks'] ?? 0,
                'followers_count' => $account->followers_count,
            ]
        );
    }

    protected function upsertMediaInsights(SocialAccount $account, int $postId, string $igMediaId, array $response): void
    {
        if (empty($response['data'])) return;

        $date    = now()->format('Y-m-d');
        $metrics = [];

        foreach ($response['data'] as $metric) {
            $metrics[$metric['name']] = $metric['value'] ?? 0;
        }

        $engagement    = ($metrics['likes'] ?? 0) + ($metrics['comments'] ?? 0) + ($metrics['shares'] ?? 0) + ($metrics['saved'] ?? 0);
        $reach         = $metrics['reach'] ?? 1;
        $engagementRate = $reach > 0 ? round(($engagement / $reach) * 100, 4) : 0;

        InstagramInsight::updateOrCreate(
            ['social_account_id' => $account->id, 'instagram_media_id' => $igMediaId, 'date' => $date],
            [
                'post_id'         => $postId,
                'impressions'     => $metrics['impressions'] ?? 0,
                'reach'           => $metrics['reach'] ?? 0,
                'engagement'      => $engagement,
                'likes'           => $metrics['likes'] ?? 0,
                'comments'        => $metrics['comments'] ?? 0,
                'shares'          => $metrics['shares'] ?? 0,
                'saved'           => $metrics['saved'] ?? 0,
                'plays'           => $metrics['plays'] ?? 0,
                'video_views'     => $metrics['video_views'] ?? 0,
                'engagement_rate' => $engagementRate,
                'raw_data'        => $response,
            ]
        );

        Analytics::updateOrCreate(
            ['social_account_id' => $account->id, 'post_id' => $postId, 'platform' => 'instagram', 'date' => $date],
            [
                'impressions'     => $metrics['impressions'] ?? 0,
                'reach'           => $metrics['reach'] ?? 0,
                'likes'           => $metrics['likes'] ?? 0,
                'comments'        => $metrics['comments'] ?? 0,
                'shares'          => $metrics['shares'] ?? 0,
                'saves'           => $metrics['saved'] ?? 0,
                'engagement_rate' => $engagementRate,
                'video_views'     => $metrics['video_views'] ?? 0,
            ]
        );
    }
}
