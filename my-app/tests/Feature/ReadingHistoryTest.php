<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ReadingHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/readings/history');

        $response->assertStatus(401);
    }

    public function test_returns_trend_and_weekly_shapes()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 8.4]);

        $response = $this->actingAs($user)->getJson('/api/readings/history');

        $response->assertOk()
            ->assertJsonCount(1, 'trend')
            ->assertJsonPath('trend.0.value', 8.4)
            ->assertJsonCount(7, 'weekly')
            ->assertJsonPath('weekly.6.is_today', true);
    }
}
