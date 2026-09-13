<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SensorReadingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_sensor_reading_with_type_and_value()
    {
        $reading = SensorReading::factory()->create([
            'type' => 'TEMPERATURE',
            'value' => 27.5,
        ]);

        $this->assertSame('TEMPERATURE', $reading->type);
        $this->assertIsFloat($reading->value);
        $this->assertSame(27.5, $reading->value);

        $this->assertDatabaseHas('sensor_readings', ['type' => 'TEMPERATURE', 'value' => 27.5]);
    }
}
