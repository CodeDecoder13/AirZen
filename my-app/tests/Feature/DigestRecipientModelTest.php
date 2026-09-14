<?php

namespace Tests\Feature;

use App\Models\DigestRecipient;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DigestRecipientModelTest extends TestCase
{
    use RefreshDatabase;

    public function test_stores_an_email()
    {
        $recipient = DigestRecipient::factory()->create(['email' => 'ops@airzen.test']);

        $this->assertSame('ops@airzen.test', $recipient->email);
        $this->assertDatabaseHas('digest_recipients', ['email' => 'ops@airzen.test']);
    }

    public function test_email_must_be_unique()
    {
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);

        $this->expectException(\Illuminate\Database\QueryException::class);
        DigestRecipient::factory()->create(['email' => 'dupe@airzen.test']);
    }
}
