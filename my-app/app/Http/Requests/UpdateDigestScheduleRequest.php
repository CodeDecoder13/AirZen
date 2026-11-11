<?php

declare(strict_types=1);

namespace App\Http\Requests;

use App\Models\DigestSchedule;
use Illuminate\Foundation\Http\FormRequest;

final class UpdateDigestScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /** @return array<string, mixed> */
    public function rules(): array
    {
        return [
            'enabled' => ['required', 'boolean'],
            'mode' => ['required', 'in:daily,interval'],
            'interval_minutes' => ['required_if:mode,interval', 'nullable', 'integer', 'min:'.DigestSchedule::MIN_INTERVAL_MINUTES, 'max:10080'],
            'send_at' => ['required_if:mode,daily', 'nullable', 'date_format:H:i'],
        ];
    }
}
