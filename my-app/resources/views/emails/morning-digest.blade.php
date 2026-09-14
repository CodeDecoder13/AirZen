<x-mail::message>
# Good morning

Here's the AirZen summary for {{ \Illuminate\Support\Carbon::parse($digest['date'])->format('l, F j') }}, based on today's readings so far.

@if ($digest['aqi'] !== null)
## Morning AQI: {{ round($digest['aqi']) }} &middot; {{ $digest['status'] }}
@else
## Not enough PM2.5 readings yet this morning to calculate an AQI.
@endif

## Parameters (today's average)

- **Temperature/Humidity:** {{ $digest['readings']['temperature'] !== null ? number_format($digest['readings']['temperature'], 1).' °C' : 'No data yet' }} / {{ $digest['readings']['humidity'] !== null ? number_format($digest['readings']['humidity'], 1).'%' : 'No data yet' }}
- **PM2.5:** {{ $digest['readings']['particulate_matter'] !== null ? number_format($digest['readings']['particulate_matter'], 1).' µg/m³' : 'No data yet' }}
- **Nitrogen:** {{ $digest['readings']['nitrogen'] !== null ? number_format($digest['readings']['nitrogen'], 1) : 'No data yet' }}
- **Carbon Monoxide:** {{ $digest['readings']['co'] !== null ? number_format($digest['readings']['co'], 2) : 'No data yet' }}

@if (! empty($digest['recommendations']))
## Recommendations

@foreach ($digest['recommendations'] as $tip)
- {{ $tip }}
@endforeach
@endif

<x-mail::button :url="route('dashboard')">
Open your dashboard
</x-mail::button>

Thanks,<br>
AirZen
</x-mail::message>
