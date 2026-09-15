<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>AirZen — Reading History</title>
    <style>
        body { font-family: sans-serif; color: #1D352D; font-size: 11px; }
        h1 { font-size: 18px; margin-bottom: 2px; }
        p.meta { color: #6B8577; margin-top: 0; margin-bottom: 16px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { text-align: left; padding: 6px 8px; border-bottom: 1px solid #EEF1E9; }
        th { text-transform: uppercase; font-size: 9px; letter-spacing: 0.06em; color: #6B8577; }
        tbody tr:nth-child(even) { background: #F7F8F1; }
    </style>
</head>
<body>
    <h1>AirZen — Indoor Air Quality Reading History</h1>
    <p class="meta">
        Generated {{ $generatedAt->format('M j, Y g:i A') }}
        @if (! empty($filters['type']))
            &middot; Type: {{ $filters['type'] }}
        @endif
        @if (! empty($filters['from']))
            &middot; From: {{ $filters['from'] }}
        @endif
        @if (! empty($filters['to']))
            &middot; To: {{ $filters['to'] }}
        @endif
        &middot; {{ $readings->count() }} reading(s)
    </p>
    <table>
        <thead>
            <tr>
                <th>Type</th>
                <th>Value</th>
                <th>Recorded At</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($readings as $reading)
                <tr>
                    <td>{{ $reading->type }}</td>
                    <td>{{ $reading->value }}</td>
                    <td>{{ $reading->created_at->format('M j, Y g:i:s A') }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
