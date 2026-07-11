<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Posts Report — CRM Social Media</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { font-family: 'DejaVu Sans', sans-serif; font-size: 11px; color: #1e293b; background: #fff; }
        .header {
            background: linear-gradient(135deg, #4f46e5 0%, #7c3aed 100%);
            color: #fff; padding: 24px 32px; margin-bottom: 24px;
        }
        .header h1 { font-size: 22px; font-weight: 700; margin-bottom: 4px; }
        .header p  { font-size: 12px; opacity: 0.85; }
        .meta { display: flex; gap: 24px; padding: 0 32px 16px; }
        .meta-item { text-align: center; }
        .meta-item .value { font-size: 20px; font-weight: 700; color: #4f46e5; }
        .meta-item .label { font-size: 10px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; }
        table { width: 100%; border-collapse: collapse; margin: 0 16px; }
        thead tr { background: #4f46e5; color: #fff; }
        thead th { padding: 9px 12px; text-align: left; font-size: 10px; text-transform: uppercase; letter-spacing: 0.5px; }
        tbody tr:nth-child(even) { background: #f8fafc; }
        tbody tr:hover { background: #eef2ff; }
        tbody td { padding: 8px 12px; border-bottom: 1px solid #e2e8f0; }
        .badge {
            display: inline-block; padding: 2px 8px; border-radius: 999px;
            font-size: 9px; font-weight: 600; text-transform: uppercase;
        }
        .badge-published  { background: #d1fae5; color: #065f46; }
        .badge-scheduled  { background: #dbeafe; color: #1d4ed8; }
        .badge-draft      { background: #f1f5f9; color: #475569; }
        .badge-failed     { background: #fee2e2; color: #991b1b; }
        .badge-cancelled  { background: #fef3c7; color: #92400e; }
        .footer { text-align: center; padding: 16px; color: #94a3b8; font-size: 9px; margin-top: 24px; }
    </style>
</head>
<body>

<div class="header">
    <h1>📊 Posts Report</h1>
    <p>Generated on {{ now()->format('F j, Y \a\t H:i T') }} &bull; CRM Social Media Management System</p>
</div>

<div class="meta" style="padding: 8px 32px 20px;">
    <div class="meta-item" style="background:#eef2ff;padding:12px 20px;border-radius:8px;">
        <div class="value">{{ $posts->count() }}</div>
        <div class="label">Total Posts</div>
    </div>
    <div class="meta-item" style="background:#d1fae5;padding:12px 20px;border-radius:8px;">
        <div class="value" style="color:#059669">{{ $posts->where('status','published')->count() }}</div>
        <div class="label">Published</div>
    </div>
    <div class="meta-item" style="background:#dbeafe;padding:12px 20px;border-radius:8px;">
        <div class="value" style="color:#1d4ed8">{{ $posts->where('status','scheduled')->count() }}</div>
        <div class="label">Scheduled</div>
    </div>
    <div class="meta-item" style="background:#f1f5f9;padding:12px 20px;border-radius:8px;">
        <div class="value" style="color:#475569">{{ $posts->where('status','draft')->count() }}</div>
        <div class="label">Drafts</div>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Author</th>
            <th>Status</th>
            <th>Type</th>
            <th>Platforms</th>
            <th>Published At</th>
            <th>Created At</th>
        </tr>
    </thead>
    <tbody>
        @forelse($posts as $index => $post)
        <tr>
            <td>{{ $index + 1 }}</td>
            <td><strong>{{ Str::limit($post->title, 45) }}</strong></td>
            <td>{{ $post->user?->name ?? '—' }}</td>
            <td><span class="badge badge-{{ $post->status }}">{{ $post->status }}</span></td>
            <td>{{ $post->post_type }}</td>
            <td>{{ implode(', ', $post->platforms) ?: '—' }}</td>
            <td>{{ $post->publish_at?->format('M d, Y H:i') ?? '—' }}</td>
            <td>{{ $post->created_at?->format('M d, Y') }}</td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:24px;color:#94a3b8">No posts found.</td></tr>
        @endforelse
    </tbody>
</table>

<div class="footer">
    CRM Social Media Management System &bull; Confidential Report &bull; Page 1
</div>

</body>
</html>
