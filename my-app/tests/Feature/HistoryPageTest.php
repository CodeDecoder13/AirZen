<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $this->get('/history')->assertRedirect('/login');
    }

    public function test_renders_with_default_30_day_range()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $response = $this->actingAs($user)->get('/history');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('History')
            ->where('days', 30)
            ->has('trend')
            ->has('bandDistribution')
            ->has('readings.data', 1));
    }

    public function test_accepts_a_custom_day_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history?days=90');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->where('days', 90));
    }

    public function test_falls_back_to_30_days_for_an_unsupported_range()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history?days=13');

        $response->assertInertia(fn ($page) => $page->where('days', 30));
    }

    public function test_filters_readings_by_type()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0]);

        $response = $this->actingAs($user)->get('/history?type=TEMPERATURE');

        $response->assertInertia(fn ($page) => $page->has('readings.data', 1));
    }
}
