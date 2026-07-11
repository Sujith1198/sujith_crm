<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AnalyticsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * AnalyticsController
 * Returns aggregated analytics data with period and platform filtering.
 */
class AnalyticsController extends Controller
{
    public function __construct(protected AnalyticsService $analyticsService)
    {
        $this->middleware('auth:api');
    }

    public function index(Request $request): JsonResponse
    {
        $request->validate([
            'period'    => 'nullable|in:daily,weekly,monthly,yearly',
            'platform'  => 'nullable|in:facebook,instagram',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date|after_or_equal:date_from',
        ]);

        $userId = auth()->user()->isAdmin() ? null : auth()->id();
        $data   = $this->analyticsService->getAggregated($request->all(), $userId);

        return response()->json(['success' => true, 'data' => $data]);
    }

    public function summary(): JsonResponse
    {
        $userId  = auth()->user()->isAdmin() ? null : auth()->id();

        return response()->json([
            'success' => true,
            'data'    => array_merge(
                $this->analyticsService->getTodaySummary($userId),
                $this->analyticsService->getTotalSummary($userId),
                $this->analyticsService->getFollowerCounts($userId),
            ),
        ]);
    }

    public function topPosts(Request $request): JsonResponse
    {
        $userId = auth()->user()->isAdmin() ? null : auth()->id();
        $limit  = (int) $request->get('limit', 10);

        return response()->json([
            'success' => true,
            'data'    => $this->analyticsService->getTopPerformingPosts($userId, $limit),
        ]);
    }
}
