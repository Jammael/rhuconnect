<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    private const NON_ADMIN_ROLES = [
        'Doctor',
        'Nurse',
        'Midwife',
        'Data Encoder',
    ];

    public function test_non_administrator_roles_cannot_access_administrator_routes(): void
    {
        foreach (self::NON_ADMIN_ROLES as $roleName) {
            $user = $this->userWithRole($roleName);

            foreach (['/admin/dashboard', '/admin/users', '/admin/users/create'] as $uri) {
                $this
                    ->actingAs($user)
                    ->get($uri)
                    ->assertForbidden();
            }
        }
    }

    public function test_administrator_can_access_administrator_routes(): void
    {
        $administrator = $this->userWithRole('Administrator');

        foreach (['/admin/dashboard', '/admin/users', '/admin/users/create'] as $uri) {
            $this
                ->actingAs($administrator)
                ->get($uri)
                ->assertOk();
        }
    }

    public function test_inactive_user_cannot_log_in(): void
    {
        $user = $this->userWithRole('Doctor', [
            'account_status' => 'INACTIVE',
        ]);

        $response = $this->post('/login', [
            'email' => $user->email,
            'password' => 'password',
        ]);

        $this->assertGuest();
        $response->assertSessionHasErrors('email');
    }

    public function test_inactive_authenticated_session_is_blocked_by_active_middleware(): void
    {
        $user = $this->userWithRole('Doctor', [
            'account_status' => 'INACTIVE',
        ]);

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertRedirect(route('login'))
            ->assertSessionHasErrors('email');

        $this->assertGuest();
    }

    public function test_administrator_cannot_deactivate_their_own_account(): void
    {
        $administrator = $this->userWithRole('Administrator');

        $this
            ->actingAs($administrator)
            ->patch("/admin/users/{$administrator->id}/status", [
                'account_status' => 'INACTIVE',
            ])
            ->assertSessionHasErrors('account_status');

        $administrator->refresh();

        $this->assertSame('ACTIVE', $administrator->account_status);
    }

    public function test_non_administrator_cannot_bulk_update_user_statuses(): void
    {
        $user = $this->userWithRole('Doctor');

        $this
            ->actingAs($user)
            ->patch('/admin/users/bulk-status', [
                'user_ids' => [$user->id + 1],
                'account_status' => 'INACTIVE',
            ])
            ->assertForbidden();
    }

    public function test_administrator_self_id_is_excluded_from_bulk_deactivate(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $otherUser = $this->userWithRole('Doctor', ['account_status' => 'ACTIVE']);

        $response = $this
            ->actingAs($administrator)
            ->patch('/admin/users/bulk-status', [
                'user_ids' => [$administrator->id, $otherUser->id],
                'account_status' => 'INACTIVE',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('self_excluded', true)
            ->assertJsonPath('updated_count', 1);

        $administrator->refresh();
        $otherUser->refresh();

        $this->assertSame('ACTIVE', $administrator->account_status);
        $this->assertSame('INACTIVE', $otherUser->account_status);
    }

    public function test_administrator_can_bulk_update_selected_users_statuses(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $firstUser = $this->userWithRole('Doctor', ['account_status' => 'ACTIVE']);
        $secondUser = $this->userWithRole('Nurse', ['account_status' => 'ACTIVE']);

        $response = $this
            ->actingAs($administrator)
            ->patch('/admin/users/bulk-status', [
                'user_ids' => [$firstUser->id, $secondUser->id],
                'account_status' => 'INACTIVE',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('self_excluded', false)
            ->assertJsonPath('updated_count', 2);

        $firstUser->refresh();
        $secondUser->refresh();

        $this->assertSame('INACTIVE', $firstUser->account_status);
        $this->assertSame('INACTIVE', $secondUser->account_status);
    }

    public function test_generic_dashboard_routes_users_to_their_role_dashboard(): void
    {
        $expectations = [
            'Doctor' => 'Doctor Dashboard',
            'Nurse' => 'Nurse Dashboard',
            'Midwife' => 'Midwife Dashboard',
            'Data Encoder' => 'Data Encoder Dashboard',
        ];

        foreach ($expectations as $roleName => $pageTitle) {
            $this
                ->actingAs($this->userWithRole($roleName))
                ->get('/dashboard')
                ->assertOk()
                ->assertSee($pageTitle)
                ->assertSee($roleName.' Portal');
        }

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->get('/dashboard')
            ->assertRedirect(route('admin.dashboard'));
    }

    public function test_dashboard_sidebar_uses_uploaded_profile_picture_when_available(): void
    {
        Storage::fake('public');

        $user = $this->userWithRole('Doctor', [
            'avatar_path' => 'avatars/sidebar-avatar.jpg',
        ]);
        Storage::disk('public')->put($user->avatar_path, 'avatar');

        $this
            ->actingAs($user)
            ->get('/dashboard')
            ->assertOk()
            ->assertSee('/storage/avatars/sidebar-avatar.jpg', false)
            ->assertSee('Profile picture for '.$user->name, false);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
