<?php

namespace Tests\Feature;

use App\Models\Reading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardPropsTest extends TestCase
{
    use RefreshDatabase;

    public function test_passes_the_latest_reading_and_recommendation_as_initial_dashboard_props()
    {
        $user = User::factory()->create();
        Reading::factory()->create(['aqi' => 30.0, 'status' => 'Good', 'color' => '#22C55E']);

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->has('initialReading')
            ->where('initialReading.aqi', 30)
            ->has('initialRecommendation')
            ->where('initialRecommendation.status', 'Good'));
    }

    public function test_passes_null_props_when_no_readings_exist_yet()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/dashboard');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page
            ->component('Dashboard')
            ->where('initialReading', null)
            ->where('initialRecommendation', null));
    }
}
