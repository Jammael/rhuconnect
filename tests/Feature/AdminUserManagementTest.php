<?php

namespace Tests\Feature;

use App\Models\AuditLog;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminUserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_view_user_management(): void
    {
        $administrator = $this->userWithRole('Administrator');

        $response = $this
            ->actingAs($administrator)
            ->get('/admin/users');

        $response
            ->assertOk()
            ->assertSee('User Management');
    }

    public function test_non_administrator_roles_are_denied_user_management(): void
    {
        foreach (['Doctor', 'Nurse', 'Midwife', 'Data Encoder'] as $role) {
            $user = $this->userWithRole($role);

            $this
                ->actingAs($user)
                ->get('/admin/users')
                ->assertForbidden();
        }
    }

    public function test_administrator_can_create_managed_staff_roles(): void
    {
        $administrator = $this->userWithRole('Administrator');

        foreach (['Doctor', 'Nurse', 'Midwife', 'Data Encoder'] as $roleName) {
            $role = Role::firstOrCreate(['name' => $roleName]);

            $response = $this
                ->actingAs($administrator)
                ->post('/admin/users', [
                    'name' => "{$roleName} User",
                    'email' => strtolower(str_replace(' ', '.', $roleName)).'@rhuconnect.test',
                    'role_id' => $role->id,
                    'account_status' => 'ACTIVE',
                    'password' => 'Password123!',
                    'password_confirmation' => 'Password123!',
                ]);

            $response->assertRedirect();
            $this->assertDatabaseHas('users', [
                'email' => strtolower(str_replace(' ', '.', $roleName)).'@rhuconnect.test',
                'role_id' => $role->id,
                'account_status' => 'ACTIVE',
            ]);
        }

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'event' => 'admin.user_created',
        ]);
    }

    public function test_administrator_cannot_create_another_administrator_through_user_management(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $administratorRole = Role::where('name', 'Administrator')->first();

        $response = $this
            ->actingAs($administrator)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => 'Second Admin',
                'email' => 'second.admin@rhuconnect.test',
                'role_id' => $administratorRole->id,
                'account_status' => 'ACTIVE',
                'password' => 'Password123!',
                'password_confirmation' => 'Password123!',
            ]);

        $response
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors('role_id');
    }

    public function test_create_user_validation_rejects_duplicate_email_and_password_mismatch(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        User::factory()->create(['email' => 'duplicate@rhuconnect.test']);

        $response = $this
            ->actingAs($administrator)
            ->from('/admin/users/create')
            ->post('/admin/users', [
                'name' => '',
                'email' => 'duplicate@rhuconnect.test',
                'role_id' => $doctorRole->id,
                'account_status' => 'ACTIVE',
                'password' => 'Password123!',
                'password_confirmation' => 'Different123!',
            ]);

        $response
            ->assertRedirect('/admin/users/create')
            ->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_administrator_can_edit_user_without_exposing_password_hash(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');
        $nurseRole = Role::firstOrCreate(['name' => 'Nurse']);

        $response = $this
            ->actingAs($administrator)
            ->put("/admin/users/{$doctor->id}", [
                'name' => 'Updated Staff',
                'email' => 'updated.staff@rhuconnect.test',
                'role_id' => $nurseRole->id,
                'account_status' => 'INACTIVE',
                'password' => '',
                'password_confirmation' => '',
            ]);

        $response->assertRedirect(route('admin.users.show', $doctor));
        $doctor->refresh();

        $this->assertSame('Updated Staff', $doctor->name);
        $this->assertSame('Nurse', $doctor->role->name);
        $this->assertSame('INACTIVE', $doctor->account_status);

        $this
            ->actingAs($administrator)
            ->get("/admin/users/{$doctor->id}")
            ->assertOk()
            ->assertDontSee($doctor->password);
    }

    public function test_administrator_can_activate_and_deactivate_users_without_deleting_them(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');

        $this
            ->actingAs($administrator)
            ->patch("/admin/users/{$doctor->id}/status", [
                'account_status' => 'INACTIVE',
            ])
            ->assertRedirect()
            ->assertSessionHas('status', 'User account status updated successfully.');

        $doctor->refresh();

        $this->assertSame('INACTIVE', $doctor->account_status);
        $this->assertNotNull($doctor->fresh());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'event' => 'admin.user_deactivated',
        ]);

        $this->assertDatabaseHas('notifications', [
            'notifiable_type' => User::class,
            'notifiable_id' => $administrator->id,
        ]);

        $this
            ->actingAs($administrator)
            ->getJson('/notifications')
            ->assertOk()
            ->assertJsonPath('unread_count', 1)
            ->assertJsonFragment([
                'message' => "{$doctor->name}'s account was deactivated.",
                'icon' => 'user-x',
            ]);

        $notification = $administrator->notifications()->firstOrFail();

        $this
            ->actingAs($administrator)
            ->postJson("/notifications/{$notification->id}/read")
            ->assertOk()
            ->assertJsonPath('unread_count', 0);

        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_administrator_cannot_deactivate_or_demote_themselves(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);

        $this
            ->actingAs($administrator)
            ->patch("/admin/users/{$administrator->id}/status", [
                'account_status' => 'INACTIVE',
            ])
            ->assertSessionHasErrors('account_status');

        $this
            ->actingAs($administrator)
            ->put("/admin/users/{$administrator->id}", [
                'name' => $administrator->name,
                'email' => $administrator->email,
                'role_id' => $doctorRole->id,
                'account_status' => 'INACTIVE',
                'password' => '',
                'password_confirmation' => '',
            ])
            ->assertRedirect(route('admin.users.show', $administrator));

        $administrator->refresh();

        $this->assertSame('Administrator', $administrator->role->name);
        $this->assertSame('ACTIVE', $administrator->account_status);
    }

    public function test_bulk_status_update_excludes_current_administrator_and_updates_selected_users(): void
    {
        $administrator = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');
        $nurse = $this->userWithRole('Nurse');

        $response = $this
            ->actingAs($administrator)
            ->patchJson('/admin/users/bulk-status', [
                'user_ids' => [$administrator->id, $doctor->id, $nurse->id],
                'account_status' => 'INACTIVE',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('updated_count', 2)
            ->assertJsonPath('self_excluded', true);

        $administrator->refresh();
        $doctor->refresh();
        $nurse->refresh();

        $this->assertSame('ACTIVE', $administrator->account_status);
        $this->assertSame('INACTIVE', $doctor->account_status);
        $this->assertSame('INACTIVE', $nurse->account_status);
        $this->assertSame(2, $administrator->notifications()->count());

        $this->assertDatabaseHas('audit_logs', [
            'user_id' => $administrator->id,
            'event' => 'admin.user_deactivated',
        ]);
    }

    public function test_non_administrator_cannot_bulk_update_user_statuses(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $nurse = $this->userWithRole('Nurse');

        $this
            ->actingAs($doctor)
            ->patchJson('/admin/users/bulk-status', [
                'user_ids' => [$nurse->id],
                'account_status' => 'INACTIVE',
            ])
            ->assertForbidden();

        $nurse->refresh();
        $this->assertSame('ACTIVE', $nurse->account_status);
    }

    public function test_inactive_user_cannot_login(): void
    {
        $doctor = $this->userWithRole('Doctor', [
            'account_status' => 'INACTIVE',
            'password' => Hash::make('Password123!'),
        ]);

        $this->post('/login', [
            'email' => $doctor->email,
            'password' => 'Password123!',
        ]);

        $this->assertGuest();
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        $role = Role::firstOrCreate(['name' => $roleName]);

        return User::factory()->create(array_merge([
            'role_id' => $role->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
