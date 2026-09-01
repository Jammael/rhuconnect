<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\Patient;
use App\Models\Queue;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QueueTest extends TestCase
{
    use RefreshDatabase;

    public function test_check_in_creates_a_queue_entry_with_a_lane_prefixed_number(): void
    {
        $appointment = $this->todaysAppointment('Approved');

        $this
            ->actingAs($this->userWithRole('Nurse'))
            ->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular'])
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $this->assertDatabaseHas('queues', [
            'appointment_id' => $appointment->id,
            'queue_number' => 'R-001',
            'priority_type' => 'Regular',
            'queue_status' => 'Waiting',
        ]);
    }

    public function test_queue_numbers_use_separate_sequences_per_priority_lane(): void
    {
        $nurse = $this->userWithRole('Nurse');

        $firstRegular = $this->todaysAppointment('Approved');
        $senior = $this->todaysAppointment('Approved');
        $secondRegular = $this->todaysAppointment('Approved');

        $this->actingAs($nurse)->post(route('queue.check-in', $firstRegular), ['priority_type' => 'Regular']);
        $this->actingAs($nurse)->post(route('queue.check-in', $senior), ['priority_type' => 'Senior Citizen']);
        $this->actingAs($nurse)->post(route('queue.check-in', $secondRegular), ['priority_type' => 'Regular']);

        $this->assertSame('R-001', $firstRegular->queue->fresh()->queue_number);
        $this->assertSame('P-001', $senior->queue->fresh()->queue_number);
        $this->assertSame('R-002', $secondRegular->queue->fresh()->queue_number);
    }

    public function test_checking_in_the_same_appointment_twice_fails_cleanly(): void
    {
        $nurse = $this->userWithRole('Nurse');
        $appointment = $this->todaysAppointment('Approved');

        $this->actingAs($nurse)->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular'])
            ->assertSessionHasNoErrors();

        $this->actingAs($nurse)->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular'])
            ->assertSessionHasErrors('appointment');

        $this->assertSame(1, Queue::query()->where('appointment_id', $appointment->id)->count());
    }

    public function test_check_in_is_rejected_for_an_appointment_not_scheduled_today(): void
    {
        $appointment = Appointment::factory()->create([
            'appointment_date' => today()->addDay(),
            'status' => 'Approved',
        ]);

        $this
            ->actingAs($this->userWithRole('Nurse'))
            ->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular'])
            ->assertSessionHasErrors('appointment');

        $this->assertDatabaseMissing('queues', ['appointment_id' => $appointment->id]);
    }

    public function test_call_next_prioritizes_priority_lane_over_an_earlier_regular_checkin(): void
    {
        $nurse = $this->userWithRole('Nurse');
        $regular = $this->todaysAppointment('Approved');
        $senior = $this->todaysAppointment('Approved');

        $this->actingAs($nurse)->post(route('queue.check-in', $regular), ['priority_type' => 'Regular']);
        $this->actingAs($nurse)->post(route('queue.check-in', $senior), ['priority_type' => 'Senior Citizen']);

        $this->actingAs($nurse)->post(route('queue.call-next'));

        $this->assertSame('Serving', $senior->queue->fresh()->queue_status);
        $this->assertSame('Waiting', $regular->queue->fresh()->queue_status);
    }

    public function test_only_nurse_or_administrator_can_call_next(): void
    {
        $this->todaysAppointment('Approved');

        foreach (['Doctor', 'Midwife', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->post(route('queue.call-next'))
                ->assertForbidden();
        }

        $this->actingAs($this->userWithRole('Administrator'))
            ->post(route('queue.call-next'))
            ->assertSessionHasNoErrors();
    }

    public function test_completing_the_appointment_auto_completes_the_serving_queue_entry(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = $this->todaysAppointment('Approved', $doctor);

        $this->actingAs($this->userWithRole('Nurse'))->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular']);
        $this->actingAs($this->userWithRole('Nurse'))->post(route('queue.call-next'));

        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), ['status' => 'Completed', 'diagnosis' => 'Routine checkup']);

        $this->assertSame('Completed', $appointment->queue->fresh()->queue_status);
    }

    public function test_appointment_no_show_marks_the_queue_entry_skipped(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = $this->todaysAppointment('Approved', $doctor);

        $this->actingAs($this->userWithRole('Nurse'))->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular']);

        $this->actingAs($doctor)->patch(route('appointments.status', $appointment), ['status' => 'No Show']);

        $this->assertSame('Skipped', $appointment->queue->fresh()->queue_status);
    }

    public function test_appointment_cancellation_marks_the_queue_entry_cancelled(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = $this->todaysAppointment('Approved', $doctor);
        $nurse = $this->userWithRole('Nurse');

        $this->actingAs($nurse)->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular']);

        $this->actingAs($nurse)->patch(route('appointments.status', $appointment), ['status' => 'Cancelled']);

        $this->assertSame('Cancelled', $appointment->queue->fresh()->queue_status);
    }

    public function test_nurse_can_manually_skip_a_waiting_entry_but_cannot_set_it_to_serving(): void
    {
        $nurse = $this->userWithRole('Nurse');
        $appointment = $this->todaysAppointment('Approved');

        $this->actingAs($nurse)->post(route('queue.check-in', $appointment), ['priority_type' => 'Regular']);
        $queue = $appointment->queue->fresh();

        $this->actingAs($nurse)
            ->patch(route('queue.status', $queue), ['status' => 'Serving'])
            ->assertSessionHasErrors('status');

        $this->actingAs($nurse)
            ->patch(route('queue.status', $queue), ['status' => 'Skipped'])
            ->assertSessionHasNoErrors();

        $this->assertSame('Skipped', $queue->fresh()->queue_status);
    }

    private function todaysAppointment(string $status, ?User $doctor = null): Appointment
    {
        return Appointment::factory()->create([
            'patient_id' => Patient::factory()->create()->id,
            'doctor_id' => ($doctor ?? $this->userWithRole('Doctor'))->id,
            'appointment_date' => today(),
            'status' => $status,
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
