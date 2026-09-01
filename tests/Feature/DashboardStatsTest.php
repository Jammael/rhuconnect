<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\PrenatalCase;
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
            ->assertSeeInOrder(['Patients Waiting', '0', 'Assigned to you'])
            ->assertSee('Liza Mendoza')
            ->assertDontSee('Someone Else');
    }

    public function test_doctor_dashboard_scopes_patients_waiting_to_their_own_queue_entries(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $otherDoctor = $this->userWithRole('Doctor');

        $ownAppointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);
        $otherAppointment = Appointment::factory()->create([
            'doctor_id' => $otherDoctor->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);

        Queue::factory()->create(['appointment_id' => $ownAppointment->id, 'queue_number' => 'R-001', 'priority_type' => 'Regular', 'queue_status' => 'Waiting']);
        // A Waiting entry tied to a DIFFERENT doctor's appointment must not be counted.
        Queue::factory()->create(['appointment_id' => $otherAppointment->id, 'queue_number' => 'R-002', 'priority_type' => 'Regular', 'queue_status' => 'Waiting']);

        $this->actingAs($doctor)
            ->get('/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Patients Waiting', '1', 'Assigned to you']);
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

    public function test_midwife_dashboard_scopes_maternal_stats_to_their_own_prenatal_cases(): void
    {
        $midwife = $this->userWithRole('Midwife');
        $otherMidwife = $this->userWithRole('Midwife');

        // An ordinary appointment with no prenatal case link must never be
        // counted under a maternal-specific label — that would be exactly
        // the unfiltered-clinic-wide-number problem this dashboard must
        // avoid now that real prenatal case data exists.
        Appointment::factory()->create([
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);

        $ownCase = PrenatalCase::factory()->create(['midwife_id' => $midwife->id, 'status' => 'Active']);
        $otherCase = PrenatalCase::factory()->create(['midwife_id' => $otherMidwife->id, 'status' => 'Active']);

        Appointment::factory()->create([
            'prenatal_case_id' => $ownCase->id,
            'appointment_date' => today(),
            'status' => 'Completed',
        ]);
        // A case-linked appointment belonging to a DIFFERENT midwife's case
        // must not be counted either.
        Appointment::factory()->create([
            'prenatal_case_id' => $otherCase->id,
            'appointment_date' => today(),
            'status' => 'Approved',
        ]);

        $response = $this->actingAs($midwife)->get('/dashboard');

        $response->assertOk()
            ->assertSeeInOrder(["Today's Maternal Appointments", '1', 'Assigned to you'])
            ->assertSeeInOrder(['Active Prenatal Cases', '1', 'Currently managed'])
            ->assertSeeInOrder(['Completed Visits', '1', 'Completed today'])
            ->assertSeeInOrder(['Upcoming Follow-ups', '0', 'Scheduled ahead']);
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

    /**
     * dashboard.stats-data is the JSON path polled every 10s by
     * dashboards.partials.overview — it must stay in lockstep with the
     * stats baked into the page's own initial render for every role, or
     * polling would silently diverge from what a fresh page load shows.
     */
    public function test_stats_data_endpoint_matches_the_dashboard_page_stat_labels_for_each_role(): void
    {
        $expectedLabels = [
            'Administrator' => ["Today's Appointments", 'Patients in Queue', 'Completed Visits', 'Available Doctors'],
            'Doctor' => ["Today's Appointments", 'Patients Waiting', 'Completed Today', 'Next Appointment'],
            'Nurse' => ['Patients in Queue', 'Vitals Recorded Today', 'Waiting for Doctor', 'Priority Cases'],
            'Midwife' => ["Today's Maternal Appointments", 'Active Prenatal Cases', 'Completed Visits', 'Upcoming Follow-ups'],
            'Data Encoder' => ['Patients Encoded Today', 'Pending Entries', 'Appointments Entered Today', 'SMS Sent Today'],
        ];

        foreach ($expectedLabels as $roleName => $labels) {
            $user = $this->userWithRole($roleName);
            $pageUrl = $roleName === 'Administrator' ? route('admin.dashboard') : route('dashboard');

            $pageResponse = $this->actingAs($user)->get($pageUrl);
            $pageResponse->assertOk();

            foreach ($labels as $label) {
                $pageResponse->assertSee($label);
            }

            $jsonResponse = $this->actingAs($user)->getJson(route('dashboard.stats-data'));
            $jsonResponse->assertOk();

            $jsonStats = $jsonResponse->json('stats');

            $this->assertCount(count($labels), $jsonStats, "Role {$roleName}: stat count mismatch between page and JSON.");
            $this->assertSame($labels, array_column($jsonStats, 'label'), "Role {$roleName}: stat labels/order mismatch between page and JSON.");

            foreach ($jsonStats as $stat) {
                $this->assertArrayHasKey('value', $stat);
                $this->assertArrayHasKey('caption', $stat);
                $this->assertArrayHasKey('tone', $stat);
            }
        }
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
