<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SensorReading;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/**
 * Not resolved via automatic controller injection (that would validate before
 * IotIngestController can tell a real reading apart from a plain status-page
 * visit) — IotIngestController builds a Validator from rules() manually and
 * returns a plain 422 JSON body itself on failure, since the ESP32 doesn't
 * parse a redirect response.
 */
final class StoreIotReadingRequest extends FormRequest
{
    private const KNOWN_TYPES = [
        SensorReading::TEMPERATURE,
        SensorReading::HUMIDITY,
        SensorReading::NITROGEN,
        SensorReading::C0,
        SensorReading::CO2,
        SensorReading::PARTICULATE_MATTER,
    ];

    /**
     * @return array<string, array<int, mixed>>
     */
    public function rules(): array
    {
        return [
            'val1' => ['required', 'numeric'],
            'val2' => ['required', 'numeric'],
            'type1' => ['required', 'string', Rule::in(self::KNOWN_TYPES)],
            'type2' => ['required', 'string', Rule::in(self::KNOWN_TYPES)],
        ];
    }
}
