<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\PrenatalCase;
use App\Models\Role;
use App\Models\User;
use App\Models\VisitHistory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitHistoryTest extends TestCase
{
    use RefreshDatabase;

    public function test_completing_an_appointment_with_diagnosis_creates_a_visit_history_record(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        $this
            ->actingAs($doctor)
            ->patch(route('appointments.status', $appointment), [
                'status' => 'Completed',
                'diagnosis' => 'Acute bronchitis',
                'prescription' => 'Salbutamol nebulization',
                'notes' => 'Follow up in 1 week if symptoms persist',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('visit_histories', [
            'appointment_id' => $appointment->id,
            'doctor_id' => $doctor->id,
            'diagnosis' => 'Acute bronchitis',
            'prescription' => 'Salbutamol nebulization',
        ]);
    }

    public function test_completing_an_appointment_without_a_diagnosis_is_rejected(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        $this
            ->actingAs($doctor)
            ->patch(route('appointments.status', $appointment), ['status' => 'Completed'])
            ->assertSessionHasErrors('diagnosis');

        $this->assertSame('Approved', $appointment->fresh()->status);
        $this->assertDatabaseMissing('visit_histories', ['appointment_id' => $appointment->id]);
    }

    public function test_completing_an_already_completed_appointment_fails_cleanly_instead_of_duplicating(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), [
            'status' => 'Completed',
            'diagnosis' => 'Initial diagnosis',
        ])->assertSessionHasNoErrors();

        $this
            ->actingAs($doctor)
            ->patch(route('appointments.status', $appointment), [
                'status' => 'Completed',
                'diagnosis' => 'Duplicate attempt',
            ])
            ->assertSessionHasErrors('diagnosis');

        $this->assertSame(1, VisitHistory::query()->where('appointment_id', $appointment->id)->count());
    }

    public function test_doctor_only_sees_their_own_patients_visit_history(): void
    {
        $doctorA = $this->userWithRole('Doctor');
        $doctorB = $this->userWithRole('Doctor');

        $patientA = Patient::factory()->create(['first_name' => 'Ana', 'middle_name' => null, 'last_name' => 'Villanueva']);
        $patientB = Patient::factory()->create(['first_name' => 'Bea', 'middle_name' => null, 'last_name' => 'Santos']);

        $appointmentA = Appointment::factory()->create(['doctor_id' => $doctorA->id, 'patient_id' => $patientA->id, 'status' => 'Approved']);
        $appointmentB = Appointment::factory()->create(['doctor_id' => $doctorB->id, 'patient_id' => $patientB->id, 'status' => 'Approved']);

        $this->actingAs($doctorA)->patch(route('appointments.status', $appointmentA), ['status' => 'Completed', 'diagnosis' => 'Diagnosis A']);
        $this->actingAs($doctorB)->patch(route('appointments.status', $appointmentB), ['status' => 'Completed', 'diagnosis' => 'Diagnosis B']);

        $this->actingAs($doctorA)
            ->get(route('visit-history.index'))
            ->assertOk()
            ->assertSee('Ana Villanueva')
            ->assertDontSee('Bea Santos');

        $this->actingAs($doctorA)
            ->get(route('visit-history.show', $appointmentB->visitHistory))
            ->assertForbidden();
    }

    public function test_administrator_sees_all_visit_history_records(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'Approved']);

        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), ['status' => 'Completed', 'diagnosis' => 'Some diagnosis']);

        $this->actingAs($this->userWithRole('Administrator'))
            ->get(route('visit-history.index'))
            ->assertOk()
            ->assertSee($appointment->fresh()->patient->full_name);
    }

    public function test_nurse_and_data_encoder_have_read_only_access(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'Approved']);
        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), ['status' => 'Completed', 'diagnosis' => 'Some diagnosis']);

        foreach (['Nurse', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get(route('visit-history.index'))
                ->assertOk();

            $this->actingAs($this->userWithRole($roleName))
                ->get(route('visit-history.show', $appointment->fresh()->visitHistory))
                ->assertOk();
        }
    }

    public function test_midwife_has_no_access_to_visit_history_unrelated_to_their_prenatal_cases(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'Approved']);
        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), ['status' => 'Completed', 'diagnosis' => 'Some diagnosis']);

        $midwife = $this->userWithRole('Midwife');

        // Index still loads (it's a valid, if empty for them, list) — the
        // real check is that this unrelated record is neither listed nor
        // directly viewable.
        $this->actingAs($midwife)->get(route('visit-history.index'))->assertOk()->assertDontSee($appointment->fresh()->patient->full_name);
        $this->actingAs($midwife)->get(route('visit-history.show', $appointment->fresh()->visitHistory))->assertForbidden();
    }

    public function test_midwife_can_view_visit_history_linked_to_their_own_prenatal_case_only(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $midwife = $this->userWithRole('Midwife');
        $otherMidwife = $this->userWithRole('Midwife');

        $ownCase = PrenatalCase::factory()->create(['midwife_id' => $midwife->id]);
        $otherCase = PrenatalCase::factory()->create(['midwife_id' => $otherMidwife->id]);

        $ownAppointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'prenatal_case_id' => $ownCase->id,
            'status' => 'Approved',
        ]);
        $otherAppointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'prenatal_case_id' => $otherCase->id,
            'status' => 'Approved',
        ]);

        $this->actingAs($doctor)->patch(route('appointments.status', $ownAppointment), ['status' => 'Completed', 'diagnosis' => 'Prenatal checkup, normal']);
        $this->actingAs($doctor)->patch(route('appointments.status', $otherAppointment), ['status' => 'Completed', 'diagnosis' => 'Prenatal checkup, other midwife']);

        $this->actingAs($midwife)
            ->get(route('visit-history.index'))
            ->assertOk()
            ->assertSee($ownAppointment->fresh()->patient->full_name)
            ->assertDontSee($otherAppointment->fresh()->patient->full_name);

        $this->actingAs($midwife)
            ->get(route('visit-history.show', $ownAppointment->fresh()->visitHistory))
            ->assertOk();

        $this->actingAs($midwife)
            ->get(route('visit-history.show', $otherAppointment->fresh()->visitHistory))
            ->assertForbidden();
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
