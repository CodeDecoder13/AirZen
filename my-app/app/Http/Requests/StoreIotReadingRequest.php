<?php

declare(strict_types=1);

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

final class StoreIotReadingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, string>
     */
    public function rules(): array
    {
        return [
            'val1' => ['required', 'numeric'],
            'val2' => ['required', 'numeric'],
            'type1' => ['required', 'string'],
            'type2' => ['required', 'string'],
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
