<?php

namespace Tests\Feature\Settings;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationsUpdateTest extends TestCase
{
    use RefreshDatabase;

    public function test_notifications_page_is_displayed()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/settings/notifications');

        $response->assertOk();
    }

    public function test_daily_digest_preference_can_be_enabled()
    {
        $user = User::factory()->create(['daily_digest_enabled' => false]);

        $response = $this->actingAs($user)->patch('/settings/notifications', [
            'daily_digest_enabled' => true,
        ]);

        $response->assertRedirect('/settings/notifications');
        $this->assertTrue($user->fresh()->daily_digest_enabled);
    }

    public function test_daily_digest_preference_can_be_disabled()
    {
        $user = User::factory()->create(['daily_digest_enabled' => true]);

        $this->actingAs($user)->patch('/settings/notifications', [
            'daily_digest_enabled' => false,
        ]);

        $this->assertFalse($user->fresh()->daily_digest_enabled);
    }

    public function test_guests_cannot_update_notification_preferences()
    {
        $response = $this->patch('/settings/notifications', ['daily_digest_enabled' => true]);

        $response->assertRedirect('/login');
    }
}
