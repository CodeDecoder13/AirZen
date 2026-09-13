<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IotIngestionTest extends TestCase
{
    use RefreshDatabase;

    public function test_saves_two_rows_from_a_valid_request()
    {
        $response = $this->get('/iot.php?val1=40.0&val2=4.5&type1=NITROGEN&type2=C0');

        $response->assertStatus(200);
        $response->assertSee('OK', false);

        $this->assertDatabaseHas('sensor_readings', ['type' => 'NITROGEN', 'value' => 40.0]);
        $this->assertDatabaseHas('sensor_readings', ['type' => 'C0', 'value' => 4.5]);
        $this->assertDatabaseCount('sensor_readings', 2);
    }

    public function test_accepts_a_co2_value_of_zero()
    {
        $response = $this->get('/iot.php?val1=0&val2=10.5&type1=CO2&type2=ParticulateMatter');

        $response->assertStatus(200);

        $this->assertDatabaseHas('sensor_readings', ['type' => 'CO2', 'value' => 0.0]);
        $this->assertDatabaseHas('sensor_readings', ['type' => 'ParticulateMatter', 'value' => 10.5]);
    }

    public function test_rejects_a_request_with_a_non_numeric_val1()
    {
        $response = $this->get('/iot.php?val1=not-a-number&val2=1&type1=TEMPERATURE&type2=HUMIDITY');

        $response->assertStatus(422);
        $this->assertDatabaseCount('sensor_readings', 0);
    }

    public function test_rejects_a_request_missing_a_required_param()
    {
        $response = $this->get('/iot.php?val1=1&type1=TEMPERATURE&type2=HUMIDITY');

        $response->assertStatus(422);
    }
}
