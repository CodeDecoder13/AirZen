<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\SensorReading;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

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

    public function authorize(): bool
    {
        return true;
    }

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

    /**
     * The ESP32 device never sends an Accept: application/json header and does
     * not parse the response body — it only logs the HTTP status code. Force a
     * plain 422 here instead of the default 302 redirect-back-on-failure
     * behavior a non-JSON request would otherwise get.
     */
    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(response()->json($validator->errors(), 422));
    }
}
