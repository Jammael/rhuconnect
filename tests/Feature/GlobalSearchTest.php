<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    private const STAFF_ROLES = [
        'Administrator',
        'Doctor',
        'Nurse',
        'Midwife',
        'Data Encoder',
    ];

    public function test_unauthenticated_request_to_search_is_redirected(): void
    {
        $this->getJson('/search?q=test')
            ->assertUnauthorized();

        $this->get('/search?q=test')
            ->assertRedirect(route('login'));
    }

    public function test_query_shorter_than_two_characters_returns_empty_result_set(): void
    {
        $admin = $this->userWithRole('Administrator');

        Patient::factory()->create(['first_name' => 'John', 'last_name' => 'Doe']);
        User::factory()->create(['name' => 'Jane Smith']);

        $response = $this->actingAs($admin)
            ->getJson('/search?q=a');

        $response->assertOk()
            ->assertExactJson([
                'patients' => [],
                'users' => [],
                'pages' => [],
            ]);

        $responseEmpty = $this->actingAs($admin)
            ->getJson('/search?q=');

        $responseEmpty->assertOk()
            ->assertExactJson([
                'patients' => [],
                'users' => [],
                'pages' => [],
            ]);
    }

    public function test_any_authenticated_role_can_search_and_find_patients(): void
    {
        $patient = Patient::factory()->create([
            'first_name' => 'Eduardo',
            'last_name' => 'Dela Cruz',
            'contact_number' => '09171234567',
        ]);

        foreach (self::STAFF_ROLES as $roleName) {
            $user = $this->userWithRole($roleName);

            $response = $this->actingAs($user)
                ->getJson('/search?q=Eduardo');

            $response->assertOk()
                ->assertJsonPath('patients.0.id', $patient->id)
                ->assertJsonPath('patients.0.name', $patient->full_name)
                ->assertJsonPath('patients.0.meta', '09171234567')
                ->assertJsonPath('patients.0.link', route('patients.show', $patient));
        }
    }

    public function test_non_administrator_roles_do_not_receive_user_staff_results(): void
    {
        $targetUser = $this->userWithRole('Doctor', [
            'name' => 'Dr. Gregory House',
            'email' => 'dr.house@rhuconnect.test',
        ]);

        $nonAdminRoles = ['Doctor', 'Nurse', 'Midwife', 'Data Encoder'];

        foreach ($nonAdminRoles as $roleName) {
            $staff = $this->userWithRole($roleName);

            $response = $this->actingAs($staff)
                ->getJson('/search?q=House');

            $response->assertOk()
                ->assertJsonCount(0, 'users')
                ->assertExactJson([
                    'patients' => [],
                    'users' => [],
                    'pages' => [],
                ]);
        }
    }

    public function test_administrator_searching_matching_term_receives_user_staff_results(): void
    {
        $administrator = $this->userWithRole('Administrator');

        $targetUser = $this->userWithRole('Doctor', [
            'name' => 'Dr. Gregory House',
            'email' => 'dr.house@rhuconnect.test',
        ]);

        $response = $this->actingAs($administrator)
            ->getJson('/search?q=House');

        $response->assertOk()
            ->assertJsonCount(1, 'users')
            ->assertJsonPath('users.0.id', $targetUser->id)
            ->assertJsonPath('users.0.name', 'Dr. Gregory House')
            ->assertJsonPath('users.0.meta', 'dr.house@rhuconnect.test')
            ->assertJsonPath('users.0.link', route('admin.users.show', $targetUser));
    }

    public function test_navigation_pages_are_filtered_by_role(): void
    {
        $admin = $this->userWithRole('Administrator');
        $nurse = $this->userWithRole('Nurse');

        // Admin searching 'User' should find 'User Management'
        $adminResponse = $this->actingAs($admin)
            ->getJson('/search?q=User');

        $adminResponse->assertOk()
            ->assertJsonFragment([
                'label' => 'User Management',
                'link' => route('admin.users.index'),
            ]);

        // Nurse searching 'User' should NOT find 'User Management'
        $nurseResponse = $this->actingAs($nurse)
            ->getJson('/search?q=User');

        $nurseResponse->assertOk()
            ->assertJsonMissing([
                'label' => 'User Management',
            ]);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}

