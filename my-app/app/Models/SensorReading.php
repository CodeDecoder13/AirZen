<?php

declare(strict_types=1);

namespace App\Models;

use Database\Factories\SensorReadingFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class SensorReading extends Model
{
    /** @use HasFactory<SensorReadingFactory> */
    use HasFactory;

    public const TEMPERATURE = 'TEMPERATURE';
    public const HUMIDITY = 'HUMIDITY';
    public const NITROGEN = 'NITROGEN';
    public const C0 = 'C0';
    public const CO2 = 'CO2';
    public const PARTICULATE_MATTER = 'ParticulateMatter';

    protected $fillable = [
        'type',
        'value',
    ];

    protected $casts = [
        'value' => 'float',
    ];
}
