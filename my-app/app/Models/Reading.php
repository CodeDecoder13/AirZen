<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\ReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class Reading extends Model
{
    /** @use HasFactory<ReadingFactory> */
    use HasFactory;

    protected $fillable = [
        'temperature',
        'humidity',
        'co',
        'nitrogen',
        'pm25',
        'aqi',
        'status',
        'color',
        'device_id',
    ];

    protected $casts = [
        'temperature' => 'float',
        'humidity' => 'float',
        'co' => 'float',
        'nitrogen' => 'float',
        'pm25' => 'float',
        'aqi' => 'float',
    ];
}
