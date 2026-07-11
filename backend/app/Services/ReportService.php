<?php

namespace App\Services;

use App\Models\Analytics;
use App\Models\Post;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\AnalyticsExport;
use App\Exports\PostsExport;

/**
 * ReportService
 * Generates and exports reports in Excel, CSV, and PDF formats.
 */
class ReportService
{
    /**
     * Generate a posts report export.
     */
    public function exportPosts(array $filters, string $format, ?int $userId = null): mixed
    {
        $query = Post::with(['user', 'media'])
            ->when($userId, fn ($q) => $q->where('user_id', $userId))
            ->when(! empty($filters['status']), fn ($q) => $q->where('status', $filters['status']))
            ->when(! empty($filters['platform']), function ($q) use ($filters) {
                if ($filters['platform'] === 'facebook') $q->where('post_to_facebook', true);
                if ($filters['platform'] === 'instagram') $q->where('post_to_instagram', true);
            })
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('created_at', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('created_at', '<=', $filters['date_to']))
            ->orderByDesc('created_at');

        $posts = $query->get();

        return match ($format) {
            'csv'   => Excel::download(new PostsExport($posts), 'posts-report.csv', \Maatwebsite\Excel\Excel::CSV),
            'pdf'   => $this->exportPostsPdf($posts),
            default => Excel::download(new PostsExport($posts), 'posts-report.xlsx'),
        };
    }

    /**
     * Generate an analytics report export.
     */
    public function exportAnalytics(array $filters, string $format, ?int $userId = null): mixed
    {
        $query = Analytics::with(['post', 'socialAccount'])
            ->when($userId, function ($q) use ($userId) {
                $q->whereHas('socialAccount', fn ($sq) => $sq->where('user_id', $userId));
            })
            ->when(! empty($filters['platform']), fn ($q) => $q->where('platform', $filters['platform']))
            ->when(! empty($filters['date_from']), fn ($q) => $q->whereDate('date', '>=', $filters['date_from']))
            ->when(! empty($filters['date_to']), fn ($q) => $q->whereDate('date', '<=', $filters['date_to']))
            ->orderByDesc('date');

        $analytics = $query->get();

        return match ($format) {
            'csv'   => Excel::download(new AnalyticsExport($analytics), 'analytics-report.csv', \Maatwebsite\Excel\Excel::CSV),
            'pdf'   => $this->exportAnalyticsPdf($analytics),
            default => Excel::download(new AnalyticsExport($analytics), 'analytics-report.xlsx'),
        };
    }

    protected function exportPostsPdf(Collection $posts): mixed
    {
        $pdf = Pdf::loadView('reports.posts', compact('posts'))
                  ->setPaper('a4', 'landscape');
        return $pdf->download('posts-report.pdf');
    }

    protected function exportAnalyticsPdf(Collection $analytics): mixed
    {
        $pdf = Pdf::loadView('reports.analytics', compact('analytics'))
                  ->setPaper('a4', 'landscape');
        return $pdf->download('analytics-report.pdf');
    }
}
