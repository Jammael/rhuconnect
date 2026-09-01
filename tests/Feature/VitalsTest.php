<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Role;
use App\Models\User;
use App\Models\Vitals;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VitalsTest extends TestCase
{
    use RefreshDatabase;

    private array $validPayload = [
        'blood_pressure_systolic' => 120,
        'blood_pressure_diastolic' => 80,
        'temperature_celsius' => 36.8,
        'pulse_rate' => 72,
        'respiratory_rate' => 16,
        'oxygen_saturation' => 98,
        'weight_kg' => 65.5,
        'height_cm' => 170.0,
        'chief_complaint' => 'Mild headache',
    ];

    public function test_nurse_and_administrator_can_record_vitals_on_an_eligible_appointment(): void
    {
        $doctor = $this->userWithRole('Doctor');

        foreach (['Nurse', 'Administrator'] as $roleName) {
            $appointment = Appointment::factory()->create([
                'doctor_id' => $doctor->id,
                'appointment_date' => today(),
                'status' => 'Approved',
            ]);

            $this
                ->actingAs($this->userWithRole($roleName))
                ->post(route('vitals.store', $appointment), $this->validPayload)
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $this->assertDatabaseHas('vitals', [
                'appointment_id' => $appointment->id,
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
                'pulse_rate' => 72,
            ]);
        }
    }

    public function test_recording_vitals_twice_for_the_same_appointment_fails_cleanly(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);
        $nurse = $this->userWithRole('Nurse');

        $this->actingAs($nurse)
            ->post(route('vitals.store', $appointment), $this->validPayload)
            ->assertSessionHasNoErrors();

        $this->actingAs($nurse)
            ->post(route('vitals.store', $appointment), $this->validPayload)
            ->assertSessionHasErrors('appointment');

        $this->assertSame(1, Vitals::query()->where('appointment_id', $appointment->id)->count());
    }

    public function test_doctor_can_view_vitals_for_their_own_patients_appointment_but_not_another_doctors(): void
    {
        $doctorA = $this->userWithRole('Doctor');
        $doctorB = $this->userWithRole('Doctor');
        $nurse = $this->userWithRole('Nurse');

        $appointmentA = Appointment::factory()->create(['doctor_id' => $doctorA->id, 'appointment_date' => today(), 'status' => 'Approved']);
        $appointmentB = Appointment::factory()->create(['doctor_id' => $doctorB->id, 'appointment_date' => today(), 'status' => 'Approved']);

        $this->actingAs($nurse)
            ->post(route('vitals.store', $appointmentA), array_merge($this->validPayload, [
                'blood_pressure_systolic' => 120,
                'blood_pressure_diastolic' => 80,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($nurse)
            ->post(route('vitals.store', $appointmentB), array_merge($this->validPayload, [
                'blood_pressure_systolic' => 110,
                'blood_pressure_diastolic' => 70,
            ]))
            ->assertSessionHasNoErrors();

        $this->actingAs($doctorA)
            ->get(route('appointments.show', $appointmentA))
            ->assertOk()
            ->assertSee('Vitals')
            ->assertSee('120/80 mmHg');

        $this->actingAs($doctorA)
            ->get(route('appointments.show', $appointmentB))
            ->assertOk()
            ->assertDontSee('110/70 mmHg')
            ->assertDontSee('Blood Pressure');
    }

    public function test_midwife_and_data_encoder_cannot_record_vitals(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);

        foreach (['Midwife', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->post(route('vitals.store', $appointment), $this->validPayload)
                ->assertForbidden();
        }

        $this->assertDatabaseCount('vitals', 0);
    }

    public function test_recording_vitals_is_blocked_for_a_completed_appointment(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'status' => 'Completed',
        ]);

        $this->actingAs($this->userWithRole('Nurse'))
            ->post(route('vitals.store', $appointment), $this->validPayload)
            ->assertForbidden();

        $this->assertDatabaseCount('vitals', 0);
    }

    public function test_recording_vitals_is_blocked_for_an_appointment_not_scheduled_today(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->addDay(),
            'status' => 'Approved',
        ]);

        $this->actingAs($this->userWithRole('Nurse'))
            ->post(route('vitals.store', $appointment), $this->validPayload)
            ->assertForbidden();

        $this->assertDatabaseCount('vitals', 0);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
