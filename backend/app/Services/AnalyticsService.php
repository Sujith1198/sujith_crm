<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\SocialAccount;
use Illuminate\Support\Facades\DB;

/**
 * AnalyticsService
 * Aggregates and formats analytics data for the dashboard and analytics pages.
 */
class AnalyticsService
{
    /**
     * Get aggregated analytics for a given date range and optional platform filter.
     * Supports: daily, weekly, monthly, yearly grouping.
     */
    public function getAggregated(array $filters, ?int $userId = null): array
    {
        $query = Analytics::query()
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('socialAccount', fn ($sq) => $sq->where('user_id', $userId));
            })
            ->when(! empty($filters['platform']), fn ($q) => $q->where('platform', $filters['platform']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']),   fn ($q) => $q->whereDate('date', '<=', $filters['date_to']));

        $period = $filters['period'] ?? 'daily';

        $groupFormat = match ($period) {
            'weekly'  => '%x-W%v',       // ISO week
            'monthly' => '%Y-%m',
            'yearly'  => '%Y',
            default   => '%Y-%m-%d',     // daily
        };

        return $query
            ->select([
                DB::raw("DATE_FORMAT(date, '{$groupFormat}') as period"),
                DB::raw('SUM(views) as views'),
                DB::raw('SUM(reach) as reach'),
                DB::raw('SUM(impressions) as impressions'),
                DB::raw('SUM(likes) as likes'),
                DB::raw('SUM(comments) as comments'),
                DB::raw('SUM(shares) as shares'),
                DB::raw('SUM(clicks) as clicks'),
                DB::raw('SUM(saves) as saves'),
                DB::raw('AVG(engagement_rate) as engagement_rate'),
                DB::raw('AVG(ctr) as ctr'),
                DB::raw('SUM(profile_visits) as profile_visits'),
                DB::raw('SUM(website_clicks) as website_clicks'),
                DB::raw('MAX(followers_count) as followers_count'),
            ])
            ->groupBy('period')
            ->orderBy('period')
            ->get()
            ->toArray();
    }

    /**
     * Get today's summary for the dashboard.
     */
    public function getTodaySummary(?int $userId = null): array
    {
        $query = Analytics::whereDate('date', today())
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('socialAccount', fn ($sq) => $sq->where('user_id', $userId));
            });

        return [
            'today_reach'    => (clone $query)->sum('reach'),
            'today_views'    => (clone $query)->sum('views'),
            'today_likes'    => (clone $query)->sum('likes'),
            'today_comments' => (clone $query)->sum('comments'),
            'today_shares'   => (clone $query)->sum('shares'),
        ];
    }

    /**
     * Get overall totals for the dashboard.
     */
    public function getTotalSummary(?int $userId = null): array
    {
        $query = Analytics::query()
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('socialAccount', fn ($sq) => $sq->where('user_id', $userId));
            });

        return [
            'total_reach'       => (clone $query)->sum('reach'),
            'total_views'       => (clone $query)->sum('views'),
            'total_impressions' => (clone $query)->sum('impressions'),
            'total_likes'       => (clone $query)->sum('likes'),
            'total_comments'    => (clone $query)->sum('comments'),
            'total_shares'      => (clone $query)->sum('shares'),
        ];
    }

    /**
     * Get top performing posts by engagement.
     */
    public function getTopPerformingPosts(?int $userId = null, int $limit = 5): mixed
    {
        return Analytics::with(['post', 'socialAccount'])
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('socialAccount', fn ($sq) => $sq->where('user_id', $userId));
            })
            ->select('post_id', DB::raw('SUM(likes + comments + shares) as total_engagement'))
            ->whereNotNull('post_id')
            ->groupBy('post_id')
            ->orderByDesc('total_engagement')
            ->limit($limit)
            ->get();
    }

    /**
     * Get follower counts per platform for the dashboard.
     */
    public function getFollowerCounts(?int $userId = null): array
    {
        $accounts = SocialAccount::where('is_active', true)
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->get();

        return [
            'facebook_followers'  => $accounts->where('platform', 'facebook')->sum('followers_count'),
            'instagram_followers' => $accounts->where('platform', 'instagram')->sum('followers_count'),
        ];
    }
}
