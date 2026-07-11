<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Illuminate\Http\Request;

/**
 * ReportController
 * Triggers report generation and returns file downloads.
 */
class ReportController extends Controller
{
    public function __construct(protected ReportService $reportService)
    {
        $this->middleware('auth:api');
        $this->middleware('role:admin')->only(['exportAll']);
    }

    public function exportPosts(Request $request): mixed
    {
        $request->validate([
            'format'    => 'nullable|in:xlsx,csv,pdf',
            'status'    => 'nullable|string',
            'platform'  => 'nullable|in:facebook,instagram',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
        ]);

        $userId = auth()->user()->isAdmin() ? null : auth()->id();
        $format = $request->get('format', 'xlsx');

        return $this->reportService->exportPosts($request->all(), $format, $userId);
    }

    public function exportAnalytics(Request $request): mixed
    {
        $request->validate([
            'format'    => 'nullable|in:xlsx,csv,pdf',
            'platform'  => 'nullable|in:facebook,instagram',
            'date_from' => 'nullable|date',
            'date_to'   => 'nullable|date',
        ]);

        $userId = auth()->user()->isAdmin() ? null : auth()->id();
        $format = $request->get('format', 'xlsx');

        return $this->reportService->exportAnalytics($request->all(), $format, $userId);
    }
}
