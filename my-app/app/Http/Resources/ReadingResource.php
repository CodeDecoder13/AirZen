<?php

declare(strict_types=1);

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

final class ReadingResource extends JsonResource
{
    public static $wrap = 'reading';

    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'temperature' => $this->temperature,
            'humidity' => $this->humidity,
            'co' => $this->co,
            'nitrogen' => $this->nitrogen,
            'pm25' => $this->pm25,
            'aqi' => $this->aqi,
            'status' => $this->status,
            'color' => $this->color,
            'device_id' => $this->device_id,
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
