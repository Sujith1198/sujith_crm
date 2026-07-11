<?php

namespace App\Jobs;

use App\Models\Analytics;
use App\Models\FacebookInsight;
use App\Models\SocialAccount;
use App\Services\FacebookService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * SyncFacebookInsightsJob
 *
 * Dispatched by the scheduler every hour.
 * Fetches page-level and post-level insights from the Facebook Graph API
 * and upserts them into facebook_insights and analytics tables.
 */
class SyncFacebookInsightsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $tries   = 2;
    public int $timeout = 300;

    public function __construct(public readonly int $socialAccountId) {}

    public function handle(FacebookService $facebookService): void
    {
        $account = SocialAccount::find($this->socialAccountId);

        if (! $account || ! $account->is_active || $account->platform !== 'facebook') {
            return;
        }

        if ($account->isTokenExpired()) {
            Log::warning("SyncFacebookInsightsJob: Token expired for account #{$account->id}");
            return;
        }

        try {
            $since = now()->subDay()->format('Y-m-d');
            $until = now()->format('Y-m-d');

            // Sync page-level insights
            $pageInsights = $facebookService->getPageInsights($account, $since, $until);
            $this->upsertPageInsights($account, $pageInsights);

            // Sync post-level insights for published posts
            $posts = $account->user->posts()
                ->where('post_to_facebook', true)
                ->where('status', 'published')
                ->whereNotNull('facebook_post_id')
                ->where('created_at', '>=', now()->subDays(30))
                ->get();

            foreach ($posts as $post) {
                try {
                    $postInsights = $facebookService->getPostInsights($account, $post->facebook_post_id);
                    $this->upsertPostInsights($account, $post->id, $post->facebook_post_id, $postInsights);
                } catch (\Exception $e) {
                    Log::warning("Failed to sync FB insights for post #{$post->id}: " . $e->getMessage());
                }
            }

            // Update followers count
            $fanCount = $facebookService->getPageFans($account);
            $account->update([
                'followers_count' => $fanCount,
                'last_synced_at'  => now(),
            ]);

            Log::info("SyncFacebookInsightsJob: Completed for account #{$account->id}");

        } catch (\Exception $e) {
            Log::error("SyncFacebookInsightsJob failed for account #{$account->id}: " . $e->getMessage());
            throw $e;
        }
    }

    protected function upsertPageInsights(SocialAccount $account, array $response): void
    {
        if (empty($response['data'])) return;

        $date  = now()->subDay()->format('Y-m-d');
        $data  = [];

        foreach ($response['data'] as $metric) {
            foreach ($metric['values'] as $value) {
                $data[$metric['name']] = $value['value'];
            }
        }

        FacebookInsight::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'facebook_post_id'  => 'page_' . $account->page_id,
                'date'              => $date,
            ],
            [
                'post_id'            => null,
                'page_fans'          => $data['page_fans'] ?? 0,
                'page_fan_adds'      => $data['page_fan_adds_unique'] ?? 0,
                'page_fan_removes'   => $data['page_fan_removes_unique'] ?? 0,
                'page_views_total'   => $data['page_views_total'] ?? 0,
                'page_impressions'   => $data['page_impressions'] ?? 0,
                'page_reach'         => $data['page_reach'] ?? 0,
                'page_engaged_users' => $data['page_engaged_users'] ?? 0,
                'raw_data'           => $response,
            ]
        );

        // Sync to aggregated analytics table
        Analytics::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'post_id'           => null,
                'platform'          => 'facebook',
                'date'              => $date,
            ],
            [
                'reach'           => $data['page_reach'] ?? 0,
                'impressions'     => $data['page_impressions'] ?? 0,
                'followers_count' => $data['page_fans'] ?? 0,
                'profile_visits'  => $data['page_views_total'] ?? 0,
            ]
        );
    }

    protected function upsertPostInsights(SocialAccount $account, int $postId, string $fbPostId, array $response): void
    {
        if (empty($response['data'])) return;

        $date    = now()->format('Y-m-d');
        $metrics = [];

        foreach ($response['data'] as $metric) {
            $metrics[$metric['name']] = $metric['values'][0]['value'] ?? 0;
        }

        FacebookInsight::updateOrCreate(
            [
                'social_account_id' => $account->id,
                'facebook_post_id'  => $fbPostId,
                'date'              => $date,
            ],
            [
                'post_id'                    => $postId,
                'impressions'                => $metrics['post_impressions'] ?? 0,
                'impressions_unique'         => $metrics['post_impressions_unique'] ?? 0,
                'impressions_paid'           => $metrics['post_impressions_paid'] ?? 0,
                'impressions_organic'        => $metrics['post_impressions_organic'] ?? 0,
                'engaged_users'              => $metrics['post_engaged_users'] ?? 0,
                'post_clicks'                => $metrics['post_clicks'] ?? 0,
                'post_clicks_unique'         => $metrics['post_clicks_unique'] ?? 0,
                'reactions_like_total'       => $metrics['post_reactions_like_total'] ?? 0,
                'reactions_love_total'       => $metrics['post_reactions_love_total'] ?? 0,
                'reactions_wow_total'        => $metrics['post_reactions_wow_total'] ?? 0,
                'reactions_haha_total'       => $metrics['post_reactions_haha_total'] ?? 0,
                'reactions_sorry_total'      => $metrics['post_reactions_sorry_total'] ?? 0,
                'reactions_anger_total'      => $metrics['post_reactions_anger_total'] ?? 0,
                'video_views'                => $metrics['post_video_views'] ?? 0,
                'video_views_10s'            => $metrics['post_video_views_10s'] ?? 0,
                'video_avg_time_watched'     => $metrics['post_video_avg_time_watched'] ?? 0,
                'raw_data'                   => $response,
            ]
        );

        // Sync to analytics
        $totalReactions = collect(['post_reactions_like_total', 'post_reactions_love_total', 'post_reactions_wow_total'])
            ->sum(fn ($k) => $metrics[$k] ?? 0);

        Analytics::updateOrCreate(
            ['social_account_id' => $account->id, 'post_id' => $postId, 'platform' => 'facebook', 'date' => $date],
            [
                'impressions'    => $metrics['post_impressions'] ?? 0,
                'reach'          => $metrics['post_impressions_unique'] ?? 0,
                'likes'          => $totalReactions,
                'clicks'         => $metrics['post_clicks'] ?? 0,
                'video_views'    => $metrics['post_video_views'] ?? 0,
            ]
        );
    }
}
