<?php

namespace Tests\Feature;

use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PatientRecordsTest extends TestCase
{
    use RefreshDatabase;

    private const STAFF_ROLES = [
        'Administrator',
        'Doctor',
        'Nurse',
        'Midwife',
        'Data Encoder',
    ];

    public function test_any_staff_role_can_create_and_view_a_patient_record(): void
    {
        foreach (self::STAFF_ROLES as $roleName) {
            $response = $this
                ->actingAs($this->userWithRole($roleName))
                ->post('/patients', $this->validPatientData([
                    'first_name' => $roleName,
                    'last_name' => 'Patient',
                    'contact_number' => '0917000000'.array_search($roleName, self::STAFF_ROLES, true),
                    'date_of_birth' => now()->subYears(21)->format('Y-m-d'),
                    'guardian_name' => $roleName.' Guardian',
                    'guardian_contact' => '0917555000'.array_search($roleName, self::STAFF_ROLES, true),
                ]));

            $response
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $patient = Patient::where('first_name', $roleName)->firstOrFail();

            $response->assertRedirect(route('patients.show', $patient));

            $this
                ->actingAs($this->userWithRole($roleName))
                ->get(route('patients.show', $patient))
                ->assertOk()
                ->assertSee($patient->full_name)
                ->assertSee('21 years old')
                ->assertSee($roleName.' Guardian')
                ->assertSee('0917555000'.array_search($roleName, self::STAFF_ROLES, true));
        }
    }

    public function test_any_staff_role_can_edit_a_patient_record(): void
    {
        foreach (self::STAFF_ROLES as $roleName) {
            $patient = Patient::factory()->create();

            $this
                ->actingAs($this->userWithRole($roleName))
                ->put(route('patients.update', $patient), $this->validPatientData([
                    'first_name' => 'Updated',
                    'last_name' => $roleName,
                ]))
                ->assertSessionHasNoErrors()
                ->assertRedirect(route('patients.show', $patient));

            $patient->refresh();

            $this->assertSame('Updated', $patient->first_name);
            $this->assertSame($roleName, $patient->last_name);
        }
    }

    public function test_only_administrator_and_nurse_can_archive_a_patient_record(): void
    {
        foreach (['Administrator', 'Nurse'] as $roleName) {
            $patient = Patient::factory()->create();

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('patients.archive', $patient))
                ->assertSessionHasNoErrors();

            $this->assertSoftDeleted($patient);
        }

        foreach (['Doctor', 'Midwife', 'Data Encoder'] as $roleName) {
            $patient = Patient::factory()->create();

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('patients.archive', $patient))
                ->assertForbidden();

            $this->assertNotSoftDeleted($patient);
        }
    }

    public function test_archived_patients_are_hidden_by_default_and_visible_with_archived_filter(): void
    {
        $activePatient = Patient::factory()->create([
            'first_name' => 'Active',
            'last_name' => 'Visible',
        ]);
        $archivedPatient = Patient::factory()->create([
            'first_name' => 'Archived',
            'last_name' => 'Hidden',
        ]);
        $archivedPatient->delete();

        $user = $this->userWithRole('Nurse');

        $this
            ->actingAs($user)
            ->get(route('patients.index'))
            ->assertOk()
            ->assertSee($activePatient->full_name)
            ->assertDontSee($archivedPatient->full_name);

        $this
            ->actingAs($user)
            ->get(route('patients.index', ['archived' => 1]))
            ->assertOk()
            ->assertSee($archivedPatient->full_name)
            ->assertDontSee($activePatient->full_name);
    }

    public function test_validation_rejects_future_date_of_birth_and_missing_required_fields(): void
    {
        $this
            ->actingAs($this->userWithRole('Data Encoder'))
            ->from(route('patients.create'))
            ->post('/patients', [
                'first_name' => '',
                'last_name' => '',
                'date_of_birth' => now()->addDay()->format('Y-m-d'),
                'sex' => '',
                'address' => '',
                'contact_number' => '',
            ])
            ->assertRedirect(route('patients.create'))
            ->assertSessionHasErrors([
                'first_name',
                'last_name',
                'date_of_birth',
                'sex',
                'address',
                'contact_number',
            ]);
    }

    private function validPatientData(array $overrides = []): array
    {
        return array_merge([
            'first_name' => 'Maria',
            'middle_name' => 'Santos',
            'last_name' => 'Reyes',
            'date_of_birth' => now()->subYears(30)->format('Y-m-d'),
            'sex' => 'FEMALE',
            'address' => 'Poblacion, Sierra Bullones, Bohol',
            'contact_number' => '09171234567',
            'philhealth_id' => '12-345678901-2',
            'blood_type' => 'O+',
            'guardian_name' => null,
            'guardian_contact' => null,
            'known_allergies' => 'Penicillin',
            'existing_conditions' => 'Hypertension',
            'current_medications' => 'Maintenance medication',
            'emergency_contact_name' => 'Ana Reyes',
            'emergency_contact_number' => '09179876543',
        ], $overrides);
    }

    private function userWithRole(string $roleName): User
    {
        return User::factory()->create([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ]);
    }
}
