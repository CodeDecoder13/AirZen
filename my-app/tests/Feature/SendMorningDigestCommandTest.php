<?php

namespace Tests\Feature;

use App\Mail\MorningAirQualityDigest;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendMorningDigestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_sends_only_to_opted_in_users_when_data_exists()
    {
        Mail::fake();

        $optedIn = User::factory()->create(['daily_digest_enabled' => true]);
        $optedOut = User::factory()->create(['daily_digest_enabled' => false]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertQueued(MorningAirQualityDigest::class, function ($mail) use ($optedIn) {
            return $mail->hasTo($optedIn->email);
        });
        Mail::assertNotQueued(MorningAirQualityDigest::class, function ($mail) use ($optedOut) {
            return $mail->hasTo($optedOut->email);
        });
    }

    public function test_sends_nothing_when_no_readings_exist_today()
    {
        Mail::fake();

        User::factory()->create(['daily_digest_enabled' => true]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }

    public function test_sends_nothing_when_no_one_is_opted_in()
    {
        Mail::fake();

        User::factory()->create(['daily_digest_enabled' => false]);
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->artisan('digest:send-morning')->assertSuccessful();

        Mail::assertNothingOutgoing();
    }
}
