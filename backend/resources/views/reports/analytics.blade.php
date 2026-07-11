<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Analytics Report — CRM Social Media</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 10px; color: #1e293b; }
        .header { background: linear-gradient(135deg, #059669 0%, #0d9488 100%); color:#fff; padding: 20px 28px; margin-bottom: 20px; }
        .header h1 { font-size: 20px; font-weight: 700; }
        .header p  { font-size: 11px; opacity: 0.85; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; }
        thead tr { background: #059669; color: #fff; }
        thead th { padding: 8px 10px; text-align: left; font-size: 9px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:nth-child(even) { background: #f0fdf4; }
        tbody td { padding: 7px 10px; border-bottom: 1px solid #d1fae5; }
        .platform-fb { color: #1877f2; font-weight: 600; }
        .platform-ig { color: #e1306c; font-weight: 600; }
        .footer { text-align: center; padding: 14px; color: #94a3b8; font-size: 9px; margin-top: 20px; }
        .number { text-align: right; }
    </style>
</head>
<body>

<div class="header">
    <h1>📈 Analytics Report</h1>
    <p>Generated on {{ now()->format('F j, Y \a\t H:i T') }}</p>
</div>

<table>
    <thead>
        <tr>
            <th>Date</th>
            <th>Platform</th>
            <th>Post</th>
            <th class="number">Views</th>
            <th class="number">Reach</th>
            <th class="number">Impressions</th>
            <th class="number">Likes</th>
            <th class="number">Comments</th>
            <th class="number">Shares</th>
            <th class="number">Eng. Rate</th>
            <th class="number">Followers</th>
        </tr>
    </thead>
    <tbody>
        @forelse($analytics as $row)
        <tr>
            <td>{{ $row->date->format('M d, Y') }}</td>
            <td class="{{ $row->platform === 'facebook' ? 'platform-fb' : 'platform-ig' }}">
                {{ ucfirst($row->platform) }}
            </td>
            <td>{{ Str::limit($row->post?->title ?? 'Account Level', 35) }}</td>
            <td class="number">{{ number_format($row->views) }}</td>
            <td class="number">{{ number_format($row->reach) }}</td>
            <td class="number">{{ number_format($row->impressions) }}</td>
            <td class="number">{{ number_format($row->likes) }}</td>
            <td class="number">{{ number_format($row->comments) }}</td>
            <td class="number">{{ number_format($row->shares) }}</td>
            <td class="number">{{ number_format($row->engagement_rate, 2) }}%</td>
            <td class="number">{{ number_format($row->followers_count) }}</td>
        </tr>
        @empty
        <tr><td colspan="11" style="text-align:center;padding:20px;color:#94a3b8">No analytics data found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">CRM Social Media Management System &bull; Analytics Report &bull; Confidential</div>

</body>
</html>
