@php
    $r = $digest['readings'];
    $aqi = $digest['aqi'];
    $accent = $digest['color'] ?? '#6B8577';
    $fmt = fn (?float $v, int $d = 1, string $unit = ''): string => $v !== null ? number_format($v, $d).$unit : '—';
    $params = [
        ['PM2.5', $fmt($r['particulate_matter'], 1), 'µg/m³'],
        ['Temperature', $fmt($r['temperature'], 1), '°C'],
        ['Humidity', $fmt($r['humidity'], 1), '%'],
        ['Nitrogen', $fmt($r['nitrogen'], 1), ''],
        ['Carbon monoxide', $fmt($r['co'], 2), ''],
    ];
    $pct = $aqi !== null ? max(2, min(98, ($aqi / 300) * 100)) : 0;
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Your AirZen morning digest</title>
</head>
<body style="margin:0;padding:0;background:#F7F8F1;font-family:'Instrument Sans',-apple-system,'Segoe UI',Helvetica,Arial,sans-serif;color:#1D352D;">
<table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F8F1;">
<tr><td align="center" style="padding:24px 12px;">
<table role="presentation" width="600" cellpadding="0" cellspacing="0" style="width:100%;max-width:600px;">

    {{-- Header --}}
    <tr><td style="background:#1A3C30;border-radius:20px 20px 0 0;padding:22px 28px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
            <td style="font-size:20px;font-weight:700;color:#FFFFFF;letter-spacing:-0.01em;">
                <span style="display:inline-block;width:28px;height:28px;line-height:28px;text-align:center;border-radius:50%;background:#C6DF58;color:#1A3C30;font-size:14px;margin-right:8px;">✦</span>AirZen
            </td>
            <td align="right" style="font-size:10px;font-weight:700;letter-spacing:0.14em;text-transform:uppercase;color:#C6DF58;">{{ ucfirst($period['label']) }} digest</td>
        </tr></table>
    </td></tr>

    {{-- Hero --}}
    <tr><td style="background:#FFFFFF;padding:32px 28px 8px;">
        <p style="margin:0 0 4px;font-size:13px;color:#6B8577;">{{ \Illuminate\Support\Carbon::parse($digest['date'])->format('l, F j') }}</p>
        <h1 style="margin:0 0 20px;font-size:26px;line-height:1.2;color:#1D352D;">{{ $period['greeting'] }}, here's your air.</h1>

        @if ($aqi !== null)
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#F7F8F1;border-radius:16px;">
            <tr><td style="padding:22px 24px;">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td style="font-size:56px;line-height:1;font-weight:800;color:#1D352D;">{{ round($aqi) }}</td>
                    <td align="right" valign="middle">
                        <span style="display:inline-block;background:{{ $accent }};color:#FFFFFF;font-size:12px;font-weight:700;padding:7px 14px;border-radius:999px;">{{ $digest['status'] }}</span>
                    </td>
                </tr></table>
                <p style="margin:6px 0 14px;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#6B8577;">Air Quality Index</p>
                {{-- AQI scale: Good → Very Unhealthy, marker at current value --}}
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="border-radius:999px;overflow:hidden;">
                    <tr>
                        <td width="17%" height="8" style="background:#22C55E;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="17%" height="8" style="background:#EAB308;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="17%" height="8" style="background:#F59E0B;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="17%" height="8" style="background:#EF4444;font-size:0;line-height:0;">&nbsp;</td>
                        <td width="32%" height="8" style="background:#A855F7;font-size:0;line-height:0;">&nbsp;</td>
                    </tr>
                </table>
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0"><tr>
                    <td width="{{ round($pct) }}%" style="font-size:0;line-height:0;">&nbsp;</td>
                    <td style="font-size:12px;line-height:14px;color:#1D352D;">▲</td>
                    <td style="font-size:0;">&nbsp;</td>
                </tr></table>
            </td></tr>
        </table>
        @else
        <p style="margin:0;padding:18px 20px;background:#F7F8F1;border-radius:16px;font-size:14px;color:#6B8577;">
            There aren't enough PM2.5 readings yet to calculate an AQI. Here's what the sensors did record:
        </p>
        @endif
    </td></tr>

    {{-- Parameters --}}
    <tr><td style="background:#FFFFFF;padding:20px 28px 8px;">
        <p style="margin:0 0 12px;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#6B8577;">Today's averages</p>
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
            @foreach ($params as [$label, $value, $unit])
            <tr>
                <td style="padding:12px 0;border-top:1px solid #E6EBDD;font-size:14px;color:#1D352D;">{{ $label }}</td>
                <td align="right" style="padding:12px 0;border-top:1px solid #E6EBDD;font-size:16px;font-weight:700;color:#1D352D;">
                    {{ $value }}@if ($value !== '—') <span style="font-size:12px;font-weight:500;color:#6B8577;">{{ $unit }}</span>@endif
                </td>
            </tr>
            @endforeach
        </table>
    </td></tr>

    {{-- Recommendations --}}
    @if (! empty($digest['recommendations']))
    <tr><td style="background:#FFFFFF;padding:20px 28px 8px;">
        <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#1A3C30;border-radius:16px;">
            <tr><td style="padding:22px 24px;">
                <p style="margin:0 0 14px;font-size:11px;font-weight:700;letter-spacing:0.12em;text-transform:uppercase;color:#C6DF58;">What to do today</p>
                @foreach ($digest['recommendations'] as $tip)
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-bottom:10px;"><tr>
                    <td width="24" valign="top" style="padding-top:2px;color:#C6DF58;font-size:14px;font-weight:700;">{{ $loop->iteration }}</td>
                    <td style="font-size:14px;line-height:1.5;color:#EAF1E4;">{{ $tip }}</td>
                </tr></table>
                @endforeach
            </td></tr>
        </table>
    </td></tr>
    @endif

    {{-- CTA --}}
    <tr><td align="center" style="background:#FFFFFF;padding:24px 28px 32px;border-radius:0 0 20px 20px;">
        <a href="{{ route('dashboard') }}" style="display:inline-block;background:#2A8362;color:#FFFFFF;font-size:14px;font-weight:700;text-decoration:none;padding:13px 28px;border-radius:999px;">Open your dashboard</a>
    </td></tr>

    <tr><td align="center" style="padding:18px 12px 0;font-size:11px;line-height:1.6;color:#6B8577;">
        You're getting this because you opted in to AirZen's daily digest.<br>
        AirZen &middot; The air, understood
    </td></tr>
</table>
</td></tr>
</table>
</body>
</html>
