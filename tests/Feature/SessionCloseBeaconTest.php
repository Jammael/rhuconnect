<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionCloseBeaconTest extends TestCase
{
    use RefreshDatabase;

    public function test_close_beacon_logs_out_authenticated_user_without_redirecting(): void
    {
        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->post('/session/close-beacon', ['_token' => csrf_token()])
            ->assertNoContent();

        $this->assertGuest();
        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $user->id,
            'event' => 'auth.logout_tab_closed',
        ]);
    }

    public function test_close_beacon_is_harmless_for_guests(): void
    {
        $this
            ->post('/session/close-beacon', ['_token' => csrf_token()])
            ->assertNoContent();

        $this->assertGuest();
    }
}
