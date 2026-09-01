<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Notifications\NewUserCreated;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_index_returns_the_authenticated_users_own_notifications_and_unread_count(): void
    {
        $user = $this->userWithRole('Administrator');
        $otherUser = $this->userWithRole('Administrator');

        $user->notify(new NewUserCreated($this->userWithRole('Doctor')));
        $otherUser->notify(new NewUserCreated($this->userWithRole('Doctor')));

        $response = $this->actingAs($user)->getJson(route('notifications.index'));

        $response->assertOk();
        $this->assertCount(1, $response->json('notifications'));
        $this->assertSame(1, $response->json('unread_count'));
    }

    public function test_mark_as_read_marks_the_notification_and_returns_updated_unread_count(): void
    {
        $user = $this->userWithRole('Administrator');
        $user->notify(new NewUserCreated($this->userWithRole('Doctor')));
        $notification = $user->notifications()->firstOrFail();

        $response = $this->actingAs($user)->postJson(route('notifications.read', $notification->id));

        $response->assertOk();
        $this->assertSame(0, $response->json('unread_count'));
        $this->assertNotNull($notification->fresh()->read_at);
    }

    public function test_mark_as_read_cannot_target_another_users_notification(): void
    {
        $owner = $this->userWithRole('Administrator');
        $owner->notify(new NewUserCreated($this->userWithRole('Doctor')));
        $notification = $owner->notifications()->firstOrFail();

        $intruder = $this->userWithRole('Administrator');

        $this->actingAs($intruder)
            ->postJson(route('notifications.read', $notification->id))
            ->assertNotFound();

        $this->assertNull($notification->fresh()->read_at);
    }

    public function test_mark_all_as_read_clears_unread_count(): void
    {
        $user = $this->userWithRole('Administrator');
        $user->notify(new NewUserCreated($this->userWithRole('Doctor')));
        $user->notify(new NewUserCreated($this->userWithRole('Doctor')));

        $response = $this->actingAs($user)->postJson(route('notifications.read-all'));

        $response->assertOk();
        $this->assertSame(0, $response->json('unread_count'));
        $this->assertSame(0, $user->unreadNotifications()->count());
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
