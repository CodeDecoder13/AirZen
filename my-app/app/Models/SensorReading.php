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
    // CO2 is always 0 on the device right now - stored for completeness but not
    // used anywhere (not in AqiCalculator, not on the dashboard).
    public const CO2 = 'CO2';
    public const PARTICULATE_MATTER = 'ParticulateMatter';

    /** @var list<string> */
    public const TYPES = [
        self::TEMPERATURE,
        self::HUMIDITY,
        self::NITROGEN,
        self::C0,
        self::CO2,
        self::PARTICULATE_MATTER,
    ];

    protected $fillable = [
        'type',
        'value',
    ];

    protected $casts = [
        'value' => 'float',
    ];
}
