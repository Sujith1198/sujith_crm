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
 * PostsExport
 * Exports posts data to Excel or CSV with styled headers.
 */
class PostsExport implements FromCollection, WithHeadings, WithMapping, WithTitle, ShouldAutoSize, WithStyles
{
    public function __construct(protected Collection $posts) {}

    public function collection(): Collection
    {
        return $this->posts;
    }

    public function title(): string
    {
        return 'Posts Report';
    }

    public function headings(): array
    {
        return [
            'ID', 'Title', 'Status', 'Post Type', 'Platforms',
            'Published At', 'Author', 'Facebook Post ID', 'Instagram Media ID',
            'Created At',
        ];
    }

    public function map($post): array
    {
        return [
            $post->id,
            $post->title,
            strtoupper($post->status),
            $post->post_type,
            implode(', ', $post->platforms),
            $post->publish_at?->format('Y-m-d H:i:s') ?? '—',
            $post->user?->name ?? '—',
            $post->facebook_post_id ?? '—',
            $post->instagram_media_id ?? '—',
            $post->created_at?->format('Y-m-d H:i:s'),
        ];
    }

    public function styles(Worksheet $sheet): array
    {
        return [
            1 => [
                'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4F46E5'],
                ],
            ],
        ];
    }
}
