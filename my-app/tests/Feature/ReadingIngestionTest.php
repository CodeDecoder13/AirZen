<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingIngestionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['services.device.key' => 'test-device-key']);
    }

    public function test_rejects_a_request_without_the_device_key_header()
    {
        $response = $this->postJson('/api/readings', [
            'temperature' => 25.0,
            'humidity' => 55.0,
            'co' => 2.0,
            'nitrogen' => 30.0,
            'pm25' => 10.0,
        ]);

        $response->assertStatus(401);
    }

    public function test_rejects_a_request_with_the_wrong_device_key()
    {
        $response = $this->postJson('/api/readings', [
            'temperature' => 25.0,
            'humidity' => 55.0,
            'co' => 2.0,
            'nitrogen' => 30.0,
            'pm25' => 10.0,
        ], ['X-Device-Key' => 'wrong-key']);

        $response->assertStatus(401);
    }

    public function test_validates_required_numeric_fields()
    {
        $response = $this->postJson('/api/readings', [
            'temperature' => 'not-a-number',
        ], ['X-Device-Key' => 'test-device-key']);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['temperature', 'humidity', 'co', 'nitrogen', 'pm25']);
    }

    public function test_stores_a_reading_with_computed_aqi_status_and_color_and_returns_the_recommendation()
    {
        $response = $this->postJson('/api/readings', [
            'temperature' => 25.0,
            'humidity' => 55.0,
            'co' => 4.5,   // sub-index 51
            'nitrogen' => 40.0, // sub-index 50
            'pm25' => 0.0, // sub-index 0
            'device_id' => 'esp32-room-204',
        ], ['X-Device-Key' => 'test-device-key']);

        $response->assertStatus(201)
            ->assertJsonPath('reading.aqi', 51)
            ->assertJsonPath('reading.status', 'Normal')
            ->assertJsonPath('reading.color', '#EAB308')
            ->assertJsonPath('recommendation.status', 'Normal')
            ->assertJsonCount(3, 'recommendation.recommendations');

        $this->assertDatabaseHas('readings', [
            'device_id' => 'esp32-room-204',
            'status' => 'Normal',
        ]);
    }
}
