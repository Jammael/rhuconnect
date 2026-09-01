<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DashboardStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_dashboard_shows_real_clinic_wide_stats(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctorA = $this->userWithRole('Doctor');
        $doctorB = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create(['first_name' => 'Maria', 'middle_name' => null, 'last_name' => 'Santos']);

        // Counts toward "Today's Appointments" and the table.
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'appointment_date' => today(),
            'appointment_time' => '08:00:00',
            'status' => 'Pending',
        ]);

        // Counts toward both "Today's Appointments" and "Completed Visits".
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorB->id,
            'appointment_date' => today(),
            'appointment_time' => '09:00:00',
            'status' => 'Completed',
        ]);

        // Cancelled today — must NOT count toward "Today's Appointments".
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'appointment_date' => today(),
            'appointment_time' => '10:00:00',
            'status' => 'Cancelled',
        ]);

        // Not today — must not be counted at all.
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'appointment_date' => today()->addDays(3),
            'status' => 'Pending',
        ]);

        // Checked-in and waiting today — counts toward "Patients in Queue".
        $waitingAppointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);
        Queue::factory()->create(['appointment_id' => $waitingAppointment->id, 'queue_status' => 'Waiting']);

        // Already completed queue entry today — must NOT count as active.
        $completedAppointment = Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctorA->id,
            'appointment_date' => today(),
            'status' => 'Completed',
        ]);
        Queue::factory()->create(['appointment_id' => $completedAppointment->id, 'queue_status' => 'Completed']);

        $response = $this->actingAs($admin)->get('/admin/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(["Today's Appointments", '4', 'Clinic-wide today'])
            ->assertSeeInOrder(['Patients in Queue', '1', 'Waiting or being served'])
            ->assertSeeInOrder(['Completed Visits', '2', 'Completed today'])
            ->assertSee('Maria Santos');
    }

    public function test_doctor_dashboard_scopes_stats_to_own_appointments_only(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $otherDoctor = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create(['first_name' => 'Liza', 'middle_name' => null, 'last_name' => 'Mendoza']);
        $otherPatient = Patient::factory()->create(['first_name' => 'Someone', 'middle_name' => null, 'last_name' => 'Else']);

        // This doctor's own appointment today — should count.
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'appointment_time' => '09:30:00',
            'status' => 'Approved',
        ]);

        // This doctor's own completed appointment today — should count toward "Completed Today".
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'appointment_time' => '08:00:00',
            'status' => 'Completed',
        ]);

        // Another doctor's appointment today — must NOT count or appear.
        Appointment::factory()->create([
            'patient_id' => $otherPatient->id,
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today(),
            'appointment_time' => '10:00:00',
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($doctor)->get('/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(["Today's Appointments", '2', 'Assigned to you'])
            ->assertSeeInOrder(['Completed Today', '1', 'Consultations closed'])
            ->assertSeeInOrder(['Patients Waiting', '0', 'Smart Queue module not yet active'])
            ->assertSee('Liza Mendoza')
            ->assertDontSee('Someone Else');
    }

    public function test_doctor_dashboard_shows_real_availability_status(): void
    {
        $doctor = $this->userWithRole('Doctor');

        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => today()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(["Today's Schedule", '08:00 AM - 12:00 PM', 'Available']);
    }

    public function test_nurse_dashboard_shows_real_queue_and_vitals_stats(): void
    {
        $nurse = $this->userWithRole('Nurse');
        $doctor = $this->userWithRole('Doctor');

        $regularPatient = Patient::factory()->create(['first_name' => 'Jose', 'middle_name' => null, 'last_name' => 'Reyes', 'birthdate' => today()->subYears(30)]);
        $seniorPatient = Patient::factory()->create(['first_name' => 'Nena', 'middle_name' => null, 'last_name' => 'Bautista', 'birthdate' => today()->subYears(70)]);

        $waitingAppointment = Appointment::factory()->create(['patient_id' => $regularPatient->id, 'doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Approved']);
        $servingAppointment = Appointment::factory()->create(['patient_id' => $seniorPatient->id, 'doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Approved']);
        $completedAppointment = Appointment::factory()->create(['patient_id' => $regularPatient->id, 'doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Completed']);

        Queue::factory()->create(['appointment_id' => $waitingAppointment->id, 'queue_number' => 'R-001', 'priority_type' => 'Regular', 'queue_status' => 'Waiting']);
        Queue::factory()->create(['appointment_id' => $servingAppointment->id, 'queue_number' => 'P-001', 'priority_type' => 'Senior Citizen', 'queue_status' => 'Serving']);
        Queue::factory()->create(['appointment_id' => $completedAppointment->id, 'queue_number' => 'R-002', 'priority_type' => 'Regular', 'queue_status' => 'Completed']);

        $response = $this->actingAs($nurse)->get('/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(['Patients in Queue', '2', 'Waiting or being served'])
            ->assertSeeInOrder(['Vitals Recorded Today', '0', 'Clinic-wide today'])
            ->assertSeeInOrder(['Waiting for Doctor', '1', 'Not yet called'])
            ->assertSeeInOrder(['Priority Cases', '1', 'Senior, PWD, or pregnant'])
            ->assertSee('No vitals recorded today yet.')
            // Current Queue table: priority lane (Senior Citizen, P-001) surfaces
            // ahead of the earlier-checked-in Regular entry (R-001).
            ->assertSeeInOrder(['P-001', 'Nena Bautista', 'Senior Citizen', 'Serving', 'R-001', 'Jose Reyes', 'Regular', 'Waiting'])
            ->assertDontSee('R-002');
    }

    public function test_midwife_dashboard_marks_unmodeled_maternal_stats_as_not_yet_active(): void
    {
        $midwife = $this->userWithRole('Midwife');

        // Real appointments exist, but since nothing distinguishes "maternal"
        // appointments in the schema, the Midwife dashboard must not surface
        // an unfiltered clinic-wide number under the maternal-specific label.
        Appointment::factory()->create([
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($midwife)->get('/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(["Today's Maternal Appointments", '0', 'Maternal care tracking not yet active'])
            ->assertSeeInOrder(['Active Prenatal Cases', '0', 'Maternal care tracking not yet active'])
            ->assertSeeInOrder(['Completed Visits', '0', 'Maternal care tracking not yet active'])
            ->assertSeeInOrder(['Upcoming Follow-ups', '0', 'Maternal care tracking not yet active']);
    }

    public function test_data_encoder_dashboard_shows_clinic_wide_activity_counts(): void
    {
        $encoder = $this->userWithRole('Data Encoder');
        $doctor = $this->userWithRole('Doctor');

        $patient = Patient::factory()->create(['first_name' => 'Alma', 'middle_name' => null, 'last_name' => 'Cortez']);
        Appointment::factory()->create([
            'patient_id' => $patient->id,
            'doctor_id' => $doctor->id,
            'appointment_date' => today()->addDay(),
            'status' => 'Pending',
        ]);

        $response = $this->actingAs($encoder)->get('/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(['Patients Encoded Today', '1', 'Clinic-wide today'])
            ->assertSeeInOrder(['Appointments Entered Today', '1', 'Clinic-wide today'])
            ->assertSeeInOrder(['Pending Entries', '0', 'Not tracked as a distinct workflow'])
            ->assertSeeInOrder(['SMS Sent Today', '0', 'Clinic-wide today'])
            ->assertSee('Alma Cortez');
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
