<?php

namespace Tests\Feature;

use App\Models\DigestRecipient;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestRecipientManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_are_redirected_to_login()
    {
        $this->get('/notifications')->assertRedirect('/login');
    }

    public function test_lists_all_recipients()
    {
        $user = User::factory()->create();
        DigestRecipient::factory()->create(['email' => 'a@airzen.test']);
        DigestRecipient::factory()->create(['email' => 'b@airzen.test']);

        $response = $this->actingAs($user)->get('/notifications');

        $response->assertOk();
        $response->assertInertia(fn ($page) => $page->component('Notifications')->has('recipients', 2));
    }

    public function test_can_add_a_recipient()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'new@airzen.test']);

        $response->assertRedirect('/notifications');
        $this->assertDatabaseHas('digest_recipients', ['email' => 'new@airzen.test']);
    }

    public function test_cannot_add_a_duplicate_email()
    {
        $user = User::factory()->create();
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'dupe@airzen.test']);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseCount('digest_recipients', 1);
    }

    public function test_rejects_an_invalid_email()
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/notifications', ['email' => 'not-an-email']);

        $response->assertSessionHasErrors('email');
    }

    public function test_can_remove_a_recipient()
    {
        $user = User::factory()->create();
        $recipient = DigestRecipient::factory()->create();

        $response = $this->actingAs($user)->delete("/notifications/{$recipient->id}");

        $response->assertRedirect('/notifications');
        $this->assertDatabaseMissing('digest_recipients', ['id' => $recipient->id]);
    }
}
