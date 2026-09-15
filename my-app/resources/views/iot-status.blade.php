<?php
/** @var array<string, ?float> $readings */
/** @var float $aqi */
/** @var string $status */
/** @var ?string $updated_at */
$lastSeen = $updated_at ? \Illuminate\Support\Carbon::parse($updated_at) : null;
$isLive = $lastSeen && $lastSeen->diffInMinutes(now()) < 5;

$badge = match (true) {
    $lastSeen === null => ['label' => 'Waiting for first reading', 'dot' => '#6B8577', 'bg' => '#EEF1E9'],
    $isLive => ['label' => 'ESP32 connected — sending data', 'dot' => '#2A8362', 'bg' => '#E4F1EA'],
    default => ['label' => 'No recent data — check the device', 'dot' => '#D97706', 'bg' => '#FBF0DD'],
};

$metrics = [
    'Temperature' => isset($readings['temperature']) ? number_format($readings['temperature'], 1).'°C' : '—',
    'Humidity' => isset($readings['humidity']) ? number_format($readings['humidity'], 1).'%' : '—',
    'PM2.5' => isset($readings['particulate_matter']) ? number_format($readings['particulate_matter'], 1).' µg/m³' : '—',
    'Nitrogen' => isset($readings['nitrogen']) ? number_format($readings['nitrogen'], 1) : '—',
    'Carbon Monoxide' => isset($readings['co']) ? number_format($readings['co'], 1) : '—',
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta http-equiv="refresh" content="15">
    <title>AirZen — Device status</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=fraunces:500,600|dm-sans:400,500,700" rel="stylesheet">
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #F7F8F1;
            color: #1D352D;
            font-family: 'DM Sans', ui-sans-serif, system-ui, sans-serif;
            padding: 24px;
        }
        .card {
            width: 100%;
            max-width: 420px;
            background: #fff;
            border-radius: 28px;
            padding: 32px;
            box-shadow: 0 1px 2px rgba(20,35,25,0.04), 0 24px 48px -24px rgba(20,35,25,0.25);
        }
        .brand { display: flex; align-items: center; gap: 8px; font-weight: 700; color: #1A3C30; margin-bottom: 24px; }
        .brand span { font-size: 13px; letter-spacing: 0.06em; text-transform: uppercase; }
        h1 { font-family: 'Fraunces', ui-serif, Georgia, serif; font-weight: 500; font-size: 22px; letter-spacing: -0.01em; margin: 0 0 4px; }
        .sub { color: #6B8577; font-size: 13px; margin: 0 0 20px; }
        .badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 14px;
            border-radius: 999px;
            font-size: 13px;
            font-weight: 700;
            background: {{ $badge['bg'] }};
            margin-bottom: 24px;
        }
        .badge .dot { width: 8px; height: 8px; border-radius: 999px; background: {{ $badge['dot'] }}; }
        .grid { display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 20px; }
        .metric { background: #F7F8F1; border-radius: 14px; padding: 12px 14px; }
        .metric .label { font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.06em; color: #6B8577; }
        .metric .value { font-size: 18px; font-weight: 700; margin-top: 2px; }
        .meta { font-size: 12px; color: #6B8577; border-top: 1px solid #EEF1E9; padding-top: 16px; }
    </style>
</head>
<body>
    <div class="card">
        <div class="brand"><span>AirZen · Device status</span></div>
        <h1>{{ $badge['label'] }}</h1>
        <p class="sub">
            @if ($lastSeen)
                Last reading received {{ $lastSeen->diffForHumans() }}.
            @else
                No readings recorded yet — this page updates itself once the device sends data.
            @endif
        </p>
        <span class="badge"><span class="dot"></span>AQI {{ number_format($aqi, 0) }} · {{ $status }}</span>
        <div class="grid">
            @foreach ($metrics as $label => $value)
                <div class="metric">
                    <div class="label">{{ $label }}</div>
                    <div class="value">{{ $value }}</div>
                </div>
            @endforeach
        </div>
        <p class="meta">This page refreshes every 15 seconds. It reads the same data the dashboard does — no test values.</p>
    </div>
</body>
</html>
