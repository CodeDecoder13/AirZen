<?php

namespace Tests\Feature;

use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class HistoryExportTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login_for_csv()
    {
        $this->get('/history/export/csv')->assertRedirect('/login');
    }

    public function test_guests_are_redirected_to_login_for_pdf()
    {
        $this->get('/history/export/pdf')->assertRedirect('/login');
    }

    public function test_csv_export_contains_readings()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 12.5]);

        $response = $this->actingAs($user)->get('/history/export/csv');

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Type,Value', $csv);
        $this->assertStringContainsString('Recorded At', $csv);
        $this->assertStringContainsString('ParticulateMatter,12.5', $csv);
    }

    public function test_csv_export_respects_the_type_filter()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'TEMPERATURE', 'value' => 25.0]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0]);

        $csv = $this->actingAs($user)->get('/history/export/csv?type=TEMPERATURE')->streamedContent();

        $this->assertStringContainsString('TEMPERATURE', $csv);
        $this->assertStringNotContainsString('ParticulateMatter', $csv);
    }

    public function test_pdf_export_downloads_a_pdf()
    {
        $user = User::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 12.5]);

        $response = $this->actingAs($user)->get('/history/export/pdf');

        $response->assertOk();
        $response->assertHeader('content-type', 'application/pdf');
    }
}
