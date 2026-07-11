<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use App\Services\PostService;
use Illuminate\Http\JsonResponse;

/**
 * DashboardController
 * Aggregates stats for the dashboard page.
 * Returns post counts, follower counts, today's metrics, and top posts.
 */
class DashboardController extends Controller
{
    public function __construct(
        protected PostService $postService,
        protected AnalyticsService $analyticsService,
    ) {
        $this->middleware('auth:api');
    }

    public function index(): JsonResponse
    {
        $user    = auth()->user();
        $isAdmin = $user->isAdmin();
        $userId  = $isAdmin ? null : $user->id;

        $postStats      = $this->postService->getDashboardStats();
        $todaySummary   = $this->analyticsService->getTodaySummary($userId);
        $totalSummary   = $this->analyticsService->getTotalSummary($userId);
        $followerCounts = $this->analyticsService->getFollowerCounts($userId);
        $topPosts       = $this->analyticsService->getTopPerformingPosts($userId, 5);

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                $postStats,
                $todaySummary,
                $totalSummary,
                $followerCounts,
                ['top_performing_posts' => $topPosts],
            ),
        ]);
    }
}
