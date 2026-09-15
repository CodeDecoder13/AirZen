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

    public function test_playground_defaults_to_particulate_matter_line_chart()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history');

        $response->assertInertia(fn ($page) => $page
            ->where('playground.metric', 'ParticulateMatter')
            ->where('playground.chartType', 'line')
            ->has('playground.daily')
            ->has('playground.buckets'));
    }

    public function test_playground_accepts_a_known_metric_and_chart_type()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'HUMIDITY', 'value' => 55.0]);

        $response = $this->actingAs($user)->get('/history?metric=HUMIDITY&chartType=pie');

        $response->assertInertia(fn ($page) => $page
            ->where('playground.metric', 'HUMIDITY')
            ->where('playground.chartType', 'pie'));
    }

    public function test_playground_falls_back_to_defaults_for_unknown_metric_or_chart_type()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/history?metric=BOGUS&chartType=scatter');

        $response->assertInertia(fn ($page) => $page
            ->where('playground.metric', 'ParticulateMatter')
            ->where('playground.chartType', 'line'));
    }
}
