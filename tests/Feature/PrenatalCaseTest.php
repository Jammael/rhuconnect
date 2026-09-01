<?php

namespace Tests\Feature;

use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\PrenatalCase;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class PrenatalCaseTest extends TestCase
{
    use RefreshDatabase;

    public function test_midwife_can_create_a_prenatal_case_for_themselves(): void
    {
        $midwife = $this->userWithRole('Midwife');
        $patient = Patient::factory()->create();

        $response = $this->actingAs($midwife)->post(route('prenatal-cases.store'), [
            'patient_id' => $patient->id,
            'last_menstrual_period' => now()->subWeeks(10)->format('Y-m-d'),
            'estimated_due_date' => now()->addDays(270)->format('Y-m-d'),
            'gravida' => 2,
            'para' => 1,
            'status' => 'Active',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertDatabaseHas('prenatal_cases', [
            'patient_id' => $patient->id,
            'midwife_id' => $midwife->id,
            'gravida' => 2,
            'para' => 1,
        ]);
    }

    public function test_administrator_can_create_a_case_and_assign_it_to_any_midwife(): void
    {
        $admin = $this->userWithRole('Administrator');
        $midwife = $this->userWithRole('Midwife');
        $patient = Patient::factory()->create();

        $this->actingAs($admin)->post(route('prenatal-cases.store'), [
            'patient_id' => $patient->id,
            'midwife_id' => $midwife->id,
            'last_menstrual_period' => now()->subWeeks(5)->format('Y-m-d'),
            'estimated_due_date' => now()->addDays(275)->format('Y-m-d'),
            'gravida' => 1,
            'para' => 0,
            'status' => 'Active',
        ])->assertSessionHasNoErrors();

        $this->assertDatabaseHas('prenatal_cases', [
            'patient_id' => $patient->id,
            'midwife_id' => $midwife->id,
        ]);
    }

    public function test_high_risk_case_requires_risk_notes(): void
    {
        $midwife = $this->userWithRole('Midwife');
        $patient = Patient::factory()->create();

        $this->actingAs($midwife)->post(route('prenatal-cases.store'), [
            'patient_id' => $patient->id,
            'last_menstrual_period' => now()->subWeeks(10)->format('Y-m-d'),
            'estimated_due_date' => now()->addDays(270)->format('Y-m-d'),
            'gravida' => 1,
            'para' => 0,
            'is_high_risk' => '1',
            'status' => 'Active',
        ])->assertSessionHasErrors('risk_notes');
    }

    public function test_midwife_only_sees_and_can_only_access_their_own_cases(): void
    {
        $midwife = $this->userWithRole('Midwife');
        $otherMidwife = $this->userWithRole('Midwife');

        $ownPatient = Patient::factory()->create(['first_name' => 'Nora', 'middle_name' => null, 'last_name' => 'Dela Cruz']);
        $otherPatient = Patient::factory()->create(['first_name' => 'Liwayway', 'middle_name' => null, 'last_name' => 'Santos']);

        $ownCase = PrenatalCase::factory()->create(['midwife_id' => $midwife->id, 'patient_id' => $ownPatient->id]);
        $otherCase = PrenatalCase::factory()->create(['midwife_id' => $otherMidwife->id, 'patient_id' => $otherPatient->id]);

        $this->actingAs($midwife)
            ->get(route('prenatal-cases.index'))
            ->assertOk()
            ->assertSee('Nora Dela Cruz')
            ->assertDontSee('Liwayway Santos');

        $this->actingAs($midwife)->get(route('prenatal-cases.show', $ownCase))->assertOk();
        $this->actingAs($midwife)->get(route('prenatal-cases.show', $otherCase))->assertForbidden();
        $this->actingAs($midwife)->get(route('prenatal-cases.edit', $otherCase))->assertForbidden();
    }

    public function test_administrator_sees_all_cases(): void
    {
        $midwifeA = $this->userWithRole('Midwife');
        $midwifeB = $this->userWithRole('Midwife');
        PrenatalCase::factory()->create(['midwife_id' => $midwifeA->id]);
        PrenatalCase::factory()->create(['midwife_id' => $midwifeB->id]);

        $response = $this->actingAs($this->userWithRole('Administrator'))->get(route('prenatal-cases.index'));

        $response->assertOk();
        $this->assertCount(2, $response->viewData('cases'));
    }

    public function test_other_roles_cannot_access_prenatal_cases(): void
    {
        $case = PrenatalCase::factory()->create();

        foreach (['Doctor', 'Nurse', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))->get(route('prenatal-cases.index'))->assertForbidden();
            $this->actingAs($this->userWithRole($roleName))->get(route('prenatal-cases.show', $case))->assertForbidden();
        }
    }

    #[DataProvider('trimesterDates')]
    public function test_trimester_is_computed_from_last_menstrual_period(int $weeksAgo, string $expectedTrimester): void
    {
        $case = PrenatalCase::factory()->create([
            'last_menstrual_period' => now()->subWeeks($weeksAgo),
        ]);

        $this->assertSame($expectedTrimester, $case->trimester);
    }

    public static function trimesterDates(): array
    {
        return [
            'week 4 is first trimester' => [4, 'First'],
            'week 12 is first trimester' => [12, 'First'],
            'week 13 is second trimester' => [13, 'Second'],
            'week 27 is second trimester' => [27, 'Second'],
            'week 28 is third trimester' => [28, 'Third'],
            'week 38 is third trimester' => [38, 'Third'],
        ];
    }

    public function test_active_for_patient_endpoint_returns_the_active_case_summary(): void
    {
        $midwife = $this->userWithRole('Midwife');
        $patient = Patient::factory()->create();
        $case = PrenatalCase::factory()->create([
            'midwife_id' => $midwife->id,
            'patient_id' => $patient->id,
            'status' => 'Active',
        ]);

        $response = $this->actingAs($this->userWithRole('Nurse'))
            ->getJson(route('prenatal-cases.active-for-patient', $patient));

        $response->assertOk();
        $this->assertSame($case->id, $response->json('case.id'));
    }

    public function test_active_for_patient_endpoint_returns_null_when_no_active_case_exists(): void
    {
        $patient = Patient::factory()->create();

        $response = $this->actingAs($this->userWithRole('Nurse'))
            ->getJson(route('prenatal-cases.active-for-patient', $patient));

        $response->assertOk();
        $this->assertNull($response->json('case'));
    }

    public function test_booking_an_appointment_can_link_it_to_the_patients_active_prenatal_case(): void
    {
        $doctor = $this->doctorWithMondayTemplate();

        $patient = Patient::factory()->create();
        $case = PrenatalCase::factory()->create(['patient_id' => $patient->id, 'status' => 'Active']);

        $nextMonday = now()->next(\Carbon\Carbon::MONDAY);

        $this->actingAs($this->userWithRole('Midwife'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'prenatal_case_id' => $case->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'Prenatal Checkup',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'prenatal_case_id' => $case->id,
        ]);
    }

    public function test_booking_cannot_link_to_a_prenatal_case_belonging_to_a_different_patient(): void
    {
        $doctor = $this->doctorWithMondayTemplate();

        $patient = Patient::factory()->create();
        $unrelatedCase = PrenatalCase::factory()->create(['status' => 'Active']);

        $nextMonday = now()->next(\Carbon\Carbon::MONDAY);

        $this->actingAs($this->userWithRole('Midwife'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'prenatal_case_id' => $unrelatedCase->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'Prenatal Checkup',
            ])
            ->assertSessionHasErrors('prenatal_case_id');
    }

    private function doctorWithMondayTemplate(): User
    {
        $doctor = $this->userWithRole('Doctor');

        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        return $doctor;
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
