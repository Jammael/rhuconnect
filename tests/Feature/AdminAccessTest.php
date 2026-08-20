<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_admin_dashboard(): void
    {
        $administratorRole = Role::factory()->administrator()->create();
        $administrator = User::factory()->create([
            'role_id' => $administratorRole->id,
        ]);

        $response = $this
            ->actingAs($administrator)
            ->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_non_administrator_cannot_access_admin_dashboard(): void
    {
        $doctor = User::factory()->create();

        $response = $this
            ->actingAs($doctor)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }
}
