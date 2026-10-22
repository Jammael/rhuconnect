<?php

namespace Tests\Feature\Auth;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Contracts\Provider;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;
use Mockery;
use Tests\TestCase;

class GoogleAuthenticationTest extends TestCase
{
    use RefreshDatabase;

    public function test_google_routes_404_when_not_configured(): void
    {
        config(['services.google.client_id' => null]);

        $this->get(route('auth.google.redirect'))->assertNotFound();
        $this->get(route('auth.google.callback'))->assertNotFound();
    }

    public function test_google_sign_in_logs_in_an_existing_matching_active_user(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $user = $this->userWithRole('Nurse', ['email' => 'nurse@rhuconnect.test']);
        $this->fakeSocialiteUser('nurse@rhuconnect.test');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('dashboard'));

        $this->assertAuthenticatedAs($user);
    }

    public function test_google_sign_in_redirects_administrator_to_admin_dashboard(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $admin = $this->userWithRole('Administrator', ['email' => 'admin@rhuconnect.test']);
        $this->fakeSocialiteUser('admin@rhuconnect.test');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('admin.dashboard'));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_google_sign_in_rejects_an_email_with_no_matching_account(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $this->fakeSocialiteUser('nobody@example.com');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_sign_in_rejects_an_inactive_account(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $this->userWithRole('Nurse', [
            'email' => 'inactive@rhuconnect.test',
            'account_status' => 'INACTIVE',
        ]);
        $this->fakeSocialiteUser('inactive@rhuconnect.test');

        $this->get(route('auth.google.callback'))
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_google_sign_in_never_creates_a_new_account(): void
    {
        config(['services.google.client_id' => 'test-client-id']);

        $this->fakeSocialiteUser('brandnew@example.com');

        $this->get(route('auth.google.callback'));

        $this->assertDatabaseMissing('users', ['email' => 'brandnew@example.com']);
    }

    private function fakeSocialiteUser(string $email): void
    {
        $socialiteUser = new SocialiteUser();
        $socialiteUser->email = $email;

        $provider = Mockery::mock(Provider::class);
        $provider->shouldReceive('user')->andReturn($socialiteUser);

        Socialite::shouldReceive('driver')->with('google')->andReturn($provider);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
