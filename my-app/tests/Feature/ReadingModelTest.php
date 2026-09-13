<?php

namespace Tests\Feature;

use App\Models\Reading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_a_reading_with_the_expected_columns_and_casts()
    {
        $reading = Reading::factory()->create([
            'temperature' => 27.5,
            'humidity' => 61.2,
            'co' => 3.1,
            'nitrogen' => 55.0,
            'pm25' => 12.4,
            'aqi' => 42.0,
            'status' => 'Good',
            'color' => '#22C55E',
            'device_id' => 'esp32-room-204',
        ]);

        $this->assertIsFloat($reading->temperature);
        $this->assertSame(27.5, $reading->temperature);
        $this->assertIsFloat($reading->aqi);
        $this->assertSame(42.0, $reading->aqi);
        $this->assertSame('Good', $reading->status);
        $this->assertSame('#22C55E', $reading->color);
        $this->assertSame('esp32-room-204', $reading->device_id);

        $this->assertDatabaseHas('readings', ['device_id' => 'esp32-room-204']);
    }

    public function test_allows_a_null_device_id()
    {
        $reading = Reading::factory()->create(['device_id' => null]);

        $this->assertNull($reading->device_id);
    }
}
