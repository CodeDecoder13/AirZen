<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\SensorReading;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SensorReading> */
final class SensorReadingFactory extends Factory
{
    protected $model = SensorReading::class;

    public function definition(): array
    {
        return [
            'type' => SensorReading::TEMPERATURE,
            'value' => $this->faker->randomFloat(1, 0, 100),
        ];
    }
}
