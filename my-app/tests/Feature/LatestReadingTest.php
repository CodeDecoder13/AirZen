<?php

namespace Tests\Feature;

use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LatestReadingTest extends TestCase
{
    use RefreshDatabase;

    public function test_requires_authentication()
    {
        $response = $this->getJson('/api/readings/latest');

        $response->assertStatus(401);
    }

    public function test_returns_the_most_recent_reading_for_an_authenticated_user()
    {
        $user = User::factory()->create();
        Reading::factory()->create(['created_at' => now()->subMinutes(10), 'aqi' => 20.0]);
        $latest = Reading::factory()->create([
            'created_at' => now(),
            'aqi' => 80.0,
            'status' => 'Normal',
            'color' => '#EAB308',
        ]);

        $response = $this->actingAs($user)->getJson('/api/readings/latest');

        $response->assertOk()
            ->assertJsonPath('reading.id', $latest->id)
            ->assertJsonPath('reading.aqi', 80)
            ->assertJsonPath('recommendation.status', 'Normal');
    }

    public function test_returns_404_when_no_readings_exist_yet()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->getJson('/api/readings/latest');

        $response->assertStatus(404);
    }
}
