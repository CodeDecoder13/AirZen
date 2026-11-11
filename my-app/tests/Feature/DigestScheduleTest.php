<?php

namespace Tests\Feature;

use App\Models\DigestSchedule;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestScheduleTest extends TestCase
{
    use RefreshDatabase;

    public function test_interval_schedule_is_due_only_after_the_interval(): void
    {
        $s = DigestSchedule::current();
        $s->update(['mode' => 'interval', 'interval_minutes' => 30, 'last_sent_at' => now()->subMinutes(29)]);
        $this->assertFalse($s->isDue());

        $s->update(['last_sent_at' => now()->subMinutes(31)]);
        $this->assertTrue($s->isDue());
    }

    public function test_daily_schedule_fires_once_per_day_in_the_digest_timezone(): void
    {
        config(['app.digest_timezone' => 'Asia/Manila']);
        $this->travelTo(now('Asia/Manila')->setTime(8, 0)); // 08:00 Manila

        $s = DigestSchedule::current();
        $s->update(['mode' => 'daily', 'send_at' => '07:00', 'last_sent_at' => now()->subDay()]);
        $this->assertTrue($s->isDue());

        $s->update(['last_sent_at' => now()]);
        $this->assertFalse($s->isDue());
        $this->assertSame('07:00', $s->nextRunAt()->setTimezone('Asia/Manila')->format('H:i'));
    }

    public function test_paused_schedule_is_never_due(): void
    {
        $s = DigestSchedule::current();
        $s->update(['enabled' => false, 'mode' => 'interval', 'last_sent_at' => now()->subYear()]);

        $this->assertFalse($s->isDue());
        $this->assertNull($s->nextRunAt());
    }

    public function test_user_can_save_a_schedule_and_clock_restarts(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('notifications.schedule'), ['enabled' => true, 'mode' => 'interval', 'interval_minutes' => 90])
            ->assertSessionHasNoErrors();

        $s = DigestSchedule::current();
        $this->assertSame('interval', $s->mode);
        $this->assertSame(90, $s->interval_minutes);
        $this->assertFalse($s->isDue());
    }

    public function test_interval_below_minimum_is_rejected(): void
    {
        $this->actingAs(User::factory()->create())
            ->put(route('notifications.schedule'), ['enabled' => true, 'mode' => 'interval', 'interval_minutes' => 1])
            ->assertSessionHasErrors('interval_minutes');
    }
}
