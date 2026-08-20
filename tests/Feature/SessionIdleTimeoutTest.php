<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SessionIdleTimeoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_expired_authenticated_session_is_logged_out_and_redirected_to_login(): void
    {
        config(['session.lifetime' => 15]);

        $user = User::factory()->create();

        $this
            ->actingAs($user)
            ->withSession(['last_activity' => now()->subMinutes(16)->timestamp])
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHas('status', 'Your session expired due to inactivity. Please sign in again.');

        $this->assertGuest();
    }

    public function test_authenticated_activity_refreshes_last_activity_timestamp(): void
    {
        config(['session.lifetime' => 15]);

        $user = User::factory()->create();
        $staleButValidActivity = now()->subMinutes(10)->timestamp;

        $this
            ->actingAs($user)
            ->withSession(['last_activity' => $staleButValidActivity])
            ->get('/dashboard')
            ->assertOk()
            ->assertSessionMissing('status')
            ->assertSessionHas('last_activity', now()->timestamp);
    }

    public function test_background_notification_poll_does_not_refresh_idle_timestamp(): void
    {
        config(['session.lifetime' => 15]);

        $user = User::factory()->create();
        $lastActivity = now()->subMinutes(10)->timestamp;

        $this
            ->actingAs($user)
            ->withSession(['last_activity' => $lastActivity])
            ->withHeader('X-Background-Poll', 'true')
            ->getJson('/notifications')
            ->assertOk()
            ->assertSessionHas('last_activity', $lastActivity);
    }
}
