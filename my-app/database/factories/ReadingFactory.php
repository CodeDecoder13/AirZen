<?php

declare(strict_types=1);

namespace Database\Factories;

use App\Models\Reading;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Reading> */
final class ReadingFactory extends Factory
{
    protected $model = Reading::class;

    public function definition(): array
    {
        return [
            'temperature' => $this->faker->randomFloat(1, 18, 32),
            'humidity' => $this->faker->randomFloat(1, 30, 90),
            'co' => $this->faker->randomFloat(2, 0, 10),
            'nitrogen' => $this->faker->randomFloat(1, 0, 120),
            'pm25' => $this->faker->randomFloat(1, 0, 60),
            'aqi' => $this->faker->randomFloat(1, 0, 150),
            'status' => 'Good',
            'color' => '#22C55E',
            'device_id' => 'esp32-room-204',
        ];
    }
}
