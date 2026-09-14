<?php

namespace Tests\Feature;

use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Models\SensorReading;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendMorningDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_to_every_recipient_when_data_exists()
    {
        Mail::fake();

        $recipient = DigestRecipient::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertQueued(MorningAirQualityDigest::class, function ($mail) use ($recipient) {
            return $mail->hasTo($recipient->email);
        });
    }

    public function test_sends_nothing_when_no_readings_exist_today()
    {
        Mail::fake();

        DigestRecipient::factory()->create();

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_sends_nothing_when_there_are_no_recipients()
    {
        Mail::fake();

        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }
}
