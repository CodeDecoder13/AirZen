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

    public function test_calculates_the_co_sub_index()
    {
        $this->assertSame(51.0, $this->calculator->calculateSubIndex('co', 4.5));
    }

    public function test_calculates_the_nitrogen_sub_index()
    {
        $this->assertSame(50.0, $this->calculator->calculateSubIndex('nitrogen', 40.0));
    }

    public function test_returns_null_for_an_unknown_pollutant()
    {
        $this->assertNull($this->calculator->calculateSubIndex('ozone', 10.0));
    }

    public function test_returns_null_when_concentration_exceeds_every_bracket()
    {
        $this->assertNull($this->calculator->calculateSubIndex('co', 999.0));
    }

    public function test_returns_the_highest_sub_index_as_the_overall_aqi()
    {
        $overall = $this->calculator->calculateOverallAqi([
            'pm25' => 0.0,
            'co' => 4.5,
            'nitrogen' => 40.0,
        ]);

        $this->assertSame(51.0, $overall);
    }
}
