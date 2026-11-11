<?php

namespace Tests\Feature;

use App\Mail\MorningAirQualityDigest;
use App\Models\DigestRecipient;
use App\Models\SensorReading;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class SendDigestNowTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_button_emails_every_recipient(): void
    {
        Mail::fake();
        $recipient = DigestRecipient::factory()->create();
        SensorReading::factory()->create(['type' => 'ParticulateMatter', 'value' => 10.0, 'created_at' => now()]);

        $this->actingAs(User::factory()->create())->post(route('notifications.send'))
            ->assertSessionHas('status');

        Mail::assertQueued(MorningAirQualityDigest::class, fn ($mail) => $mail->hasTo($recipient->email));
    }

    public function test_send_button_reports_when_there_is_no_data(): void
    {
        Mail::fake();
        DigestRecipient::factory()->create();

        $this->actingAs(User::factory()->create())->post(route('notifications.send'))
            ->assertSessionHasErrors('send');

        Mail::assertNothingOutgoing();
    }

    public function test_guests_cannot_send(): void
    {
        $this->post(route('notifications.send'))->assertRedirect(route('login'));
    }

    public function test_subject_and_greeting_follow_the_time_of_day(): void
    {
        config(['app.digest_timezone' => 'UTC']);
        $digest = ['date' => '2026-01-01', 'readings' => ['temperature' => null, 'humidity' => null, 'nitrogen' => null, 'co' => null, 'particulate_matter' => 10.0], 'aqi' => 40.0, 'status' => 'Good', 'color' => '#22C55E', 'recommendations' => []];

        foreach ([5 => 'dawn', 9 => 'morning', 14 => 'afternoon', 19 => 'evening', 23 => 'night', 2 => 'night'] as $hour => $label) {
            $this->travelTo(now('UTC')->setTime($hour, 30));
            $mail = new MorningAirQualityDigest($digest);

            $this->assertSame($label, $mail->period()['label']);
            $mail->assertHasSubject("Your {$label} air quality: Good");
        }
    }
}
