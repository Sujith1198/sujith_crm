<?php

namespace App\Exports;

use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithStyles;
use Maatwebsite\Excel\Concerns\WithTitle;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PhpOffice\PhpSpreadsheet\Style\Fill;

/**
 * AnalyticsExport
 * Exports analytics data to Excel or CSV with all engagement metrics.
 */
class AnalyticsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(protected Collection $analytics) {}

    public function collection(): Collection
    {
        return $this->analytics;
    }

    public function title(): string
    {
        return 'Analytics Report';
    }

    public function headings(): array
    {
        return [
            'Date', 'Platform', 'Post Title',
            'Views', 'Reach', 'Impressions',
            'Likes', 'Comments', 'Shares', 'Saves',
            'Engagement Rate (%)', 'CTR (%)',
            'Followers', 'Profile Visits', 'Website Clicks', 'Video Views',
        ];
    }

    public function map($row): array
    {
        return [
            $row->date->format('Y-m-d'),
            ucfirst($row->platform),
            $row->post?->title ?? 'Page/Account Level',
            $row->views,
            $row->reach,
            $row->impressions,
            $row->likes,
            $row->comments,
            $row->shares,
            $row->saves,
            number_format($row->engagement_rate, 2),
            number_format($row->ctr, 2),
            $row->followers_count,
            $row->profile_visits,
            $row->website_clicks,
            $row->video_views,
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '059669'],
                ],
            ],
        ];
    }
}
