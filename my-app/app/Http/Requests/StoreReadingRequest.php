<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

final class StoreReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // authorization is handled by the VerifyDeviceApiKey middleware
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'temperature' => ['required', 'numeric'],
            'humidity' => ['required', 'numeric'],
            'co' => ['required', 'numeric'],
            'nitrogen' => ['required', 'numeric'],
            'pm25' => ['required', 'numeric'],
            'device_id' => ['nullable', 'string', 'max:255'],
        ];
    }
}
