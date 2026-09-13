<?php

namespace Tests\Unit;

use App\Services\AqiCalculator;
use PHPUnit\Framework\TestCase;

class AqiCalculatorTest extends TestCase
{
    private AqiCalculator $calculator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->calculator = new AqiCalculator();
    }

    public function test_calculates_the_pm25_sub_index_at_the_low_edge_of_a_bracket()
    {
        $this->assertSame(0.0, $this->calculator->calculateSubIndex('pm25', 0.0));
    }

    public function test_calculates_the_pm25_sub_index_at_the_midpoint_of_a_bracket()
    {
        $result = $this->calculator->calculateSubIndex('pm25', 25.45);

        $this->assertGreaterThan(74.0, $result);
        $this->assertLessThan(77.0, $result);
    }

    public function test_calculates_the_co_sub_index_even_though_it_is_not_used_yet()
    {
        // CO breakpoint table is implemented and ready, but not yet wired into
        // calculateOverallAqi() because the device's CO reading isn't ppm-calibrated.
        $this->assertSame(51.0, $this->calculator->calculateSubIndex('co', 4.5));
    }

    public function test_returns_null_for_an_unknown_pollutant()
    {
        $this->assertNull($this->calculator->calculateSubIndex('ozone', 10.0));
    }

    public function test_returns_null_when_concentration_exceeds_every_bracket()
    {
        $this->assertNull($this->calculator->calculateSubIndex('co', 999.0));
    }

    public function test_overall_aqi_is_driven_by_pm25_only()
    {
        $overall = $this->calculator->calculateOverallAqi(['pm25' => 25.45]);

        $this->assertGreaterThan(74.0, $overall);
        $this->assertLessThan(77.0, $overall);
    }

    public function test_overall_aqi_ignores_uncalibrated_co_even_if_present()
    {
        // CO isn't ppm-calibrated yet, so it must never influence the overall AQI,
        // even when a caller passes it alongside pm25.
        $overall = $this->calculator->calculateOverallAqi(['pm25' => 0.0, 'co' => 999.0]);

        $this->assertSame(0.0, $overall);
    }

    public function test_overall_aqi_is_zero_when_pm25_is_out_of_range()
    {
        $overall = $this->calculator->calculateOverallAqi(['pm25' => 999.0]);

        $this->assertSame(0.0, $overall);
    }
}
