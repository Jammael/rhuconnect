<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\Role;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AppointmentsTest extends TestCase
{
    use RefreshDatabase;

    private const BOOKING_ROLES = ['Administrator', 'Nurse', 'Midwife', 'Data Encoder'];

    private const ALL_STAFF_ROLES = ['Administrator', 'Doctor', 'Nurse', 'Midwife', 'Data Encoder'];

    public function test_booking_role_can_create_appointment_within_available_slot(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        $response = $this
            ->actingAs($this->userWithRole('Administrator'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'General Consultation',
            ]);

        $response->assertSessionHasNoErrors()->assertRedirect();

        $this->assertDatabaseHas('appointments', [
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_time' => '08:00:00',
            'status' => 'Pending',
        ]);
    }

    public function test_booking_an_already_taken_slot_is_rejected(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => $nextMonday->format('Y-m-d'),
            'appointment_time' => '08:00:00',
            'status' => 'Pending',
        ]);

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'General Consultation',
            ])
            ->assertSessionHasErrors('appointment_time');
    }

    public function test_booking_a_slot_the_doctor_does_not_work_is_rejected(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY);

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $nextTuesday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'General Consultation',
            ])
            ->assertSessionHasErrors('appointment_time');
    }

    public function test_booking_role_can_view_create_and_edit_forms(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'Pending',
        ]);

        $admin = $this->userWithRole('Administrator');

        $this->actingAs($admin)->get(route('appointments.create'))->assertOk();
        $this->actingAs($admin)->get(route('appointments.edit', $appointment))->assertOk();
    }

    public function test_doctor_role_cannot_create_or_store_appointments(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        $this->actingAs($doctor)->get(route('appointments.create'))->assertForbidden();

        $this
            ->actingAs($doctor)
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'General Consultation',
            ])
            ->assertForbidden();
    }

    public function test_all_staff_roles_can_view_index_and_show(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'Pending',
        ]);

        foreach (self::ALL_STAFF_ROLES as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get(route('appointments.index'))
                ->assertOk();

            $this->actingAs($this->userWithRole($roleName))
                ->get(route('appointments.show', $appointment))
                ->assertOk();
        }
    }

    public function test_booking_roles_can_approve_and_cancel(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();

        foreach (self::BOOKING_ROLES as $roleName) {
            $appointment = Appointment::factory()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'status' => 'Pending',
            ]);

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('appointments.status', $appointment), ['status' => 'Approved'])
                ->assertSessionHasNoErrors()
                ->assertRedirect();

            $this->assertSame('Approved', $appointment->fresh()->status);

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('appointments.status', $appointment), ['status' => 'Cancelled'])
                ->assertSessionHasNoErrors();

            $this->assertSame('Cancelled', $appointment->fresh()->status);
        }
    }

    public function test_non_administrator_booking_roles_cannot_complete_or_mark_no_show(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();

        foreach (['Nurse', 'Midwife', 'Data Encoder'] as $roleName) {
            $appointment = Appointment::factory()->create([
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'status' => 'Approved',
            ]);

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('appointments.status', $appointment), ['status' => 'Completed'])
                ->assertForbidden();

            $this
                ->actingAs($this->userWithRole($roleName))
                ->patch(route('appointments.status', $appointment), ['status' => 'No Show'])
                ->assertForbidden();
        }
    }

    public function test_only_administrator_or_assigned_doctor_can_complete_or_mark_no_show(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $otherDoctor = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create();

        // Nurse (a booking role, but not admin/assigned doctor) cannot complete.
        $appointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        $this
            ->actingAs($this->userWithRole('Nurse'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Completed'])
            ->assertForbidden();

        // A different doctor (not assigned to this appointment) cannot complete it.
        $this
            ->actingAs($otherDoctor)
            ->patch(route('appointments.status', $appointment), ['status' => 'Completed'])
            ->assertForbidden();

        // The assigned doctor can mark it Completed.
        $this
            ->actingAs($doctor)
            ->patch(route('appointments.status', $appointment), ['status' => 'Completed', 'diagnosis' => 'Upper respiratory tract infection'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Completed', $appointment->fresh()->status);

        // Administrator can mark a different appointment as No Show.
        $secondAppointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $secondAppointment), ['status' => 'No Show'])
            ->assertSessionHasNoErrors();

        $this->assertSame('No Show', $secondAppointment->fresh()->status);
    }

    public function test_booking_an_appointment_shrinks_the_doctors_available_slots(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create();
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        $service = app(DoctorAvailabilityService::class);

        $this->assertContains('08:00', $service->getAvailableSlots($doctor, $nextMonday));

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->post(route('appointments.store'), [
                'patient_id' => $patient->id,
                'doctor_id' => $doctor->id,
                'appointment_date' => $nextMonday->format('Y-m-d'),
                'appointment_time' => '08:00',
                'purpose_of_visit' => 'General Consultation',
            ])
            ->assertSessionHasNoErrors();

        $this->assertNotContains('08:00', $service->getAvailableSlots($doctor, $nextMonday));
    }

    private function doctorWithMondayTemplate(): User
    {
        $doctor = $this->userWithRole('Doctor');

        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
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
