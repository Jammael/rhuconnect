<?php

namespace Tests\Feature;

use App\Models\Appointment;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Patient;
use App\Models\Role;
use App\Models\SmsNotification;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class SmsNotificationTest extends TestCase
{
    use RefreshDatabase;

    public function test_booking_an_appointment_creates_a_sent_sms_log_with_correct_recipient_and_message(): void
    {
        $doctor = $this->doctorWithMondayTemplate();
        $patient = Patient::factory()->create(['contact_number' => '09171234567']);
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);

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

        $appointment = Appointment::query()->where('patient_id', $patient->id)->firstOrFail();

        $this->assertDatabaseHas('sms_notifications', [
            'patient_id' => $patient->id,
            'appointment_id' => $appointment->id,
            'recipient_number' => '09171234567',
            'delivery_status' => 'Sent',
        ]);

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertNotNull($notification->sent_at);
        $this->assertStringContainsString($patient->full_name, $notification->message);
        $this->assertStringContainsString($doctor->name, $notification->message);
        $this->assertStringContainsString('General Consultation', $notification->message);
    }

    public function test_approving_an_appointment_creates_a_status_change_sms_log(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'Pending',
        ]);

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame('Sent', $notification->delivery_status);
        $this->assertNotNull($notification->sent_at);
        $this->assertStringContainsString('approved', $notification->message);
    }

    public function test_cancelling_an_appointment_creates_a_status_change_sms_log(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create();
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'Pending',
        ]);

        $this
            ->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Cancelled'])
            ->assertSessionHasNoErrors();

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();

        $this->assertSame('Sent', $notification->delivery_status);
        $this->assertStringContainsString('cancelled', $notification->message);
    }

    public function test_marking_an_appointment_completed_does_not_create_a_status_change_sms_log(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'status' => 'Approved',
        ]);

        // The booking-confirmation log already exists only if it was created
        // via the store() endpoint — this appointment was seeded directly,
        // so no SMS rows exist yet; completing it should still not add one.
        $this
            ->actingAs($doctor)
            ->patch(route('appointments.status', $appointment), [
                'status' => 'Completed',
                'diagnosis' => 'Some diagnosis',
            ])
            ->assertSessionHasNoErrors();

        $this->assertDatabaseCount('sms_notifications', 0);
    }

    public function test_administrator_and_data_encoder_can_view_the_sms_log_other_roles_are_forbidden(): void
    {
        $notification = SmsNotification::factory()->create();

        foreach (['Administrator', 'Data Encoder'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get(route('sms-notifications.index'))
                ->assertOk()
                ->assertSee($notification->patient->full_name);
        }

        foreach (['Doctor', 'Nurse', 'Midwife'] as $roleName) {
            $this->actingAs($this->userWithRole($roleName))
                ->get(route('sms-notifications.index'))
                ->assertForbidden();
        }
    }

    public function test_sms_log_view_paginates(): void
    {
        SmsNotification::factory()->count(15)->create();

        $response = $this->actingAs($this->userWithRole('Administrator'))
            ->get(route('sms-notifications.index'));

        $response->assertOk();
        $this->assertCount(10, $response->viewData('notifications'));
        $this->assertSame(15, $response->viewData('notifications')->total());

        $this->actingAs($this->userWithRole('Administrator'))
            ->get(route('sms-notifications.index', ['page' => 2]))
            ->assertOk();
    }

    public function test_real_gateway_send_marks_notification_sent_on_successful_response(): void
    {
        config(['services.semaphore.api_key' => 'test-key', 'services.semaphore.sender_name' => 'RHUCONNECT']);
        Http::fake(['api.semaphore.co/*' => Http::response([['message_id' => 1, 'status' => 'Pending']], 200)]);

        $doctor = $this->userWithRole('Doctor');
        $patient = Patient::factory()->create(['contact_number' => '0917 123 4567']);
        $appointment = Appointment::factory()->create([
            'doctor_id' => $doctor->id,
            'patient_id' => $patient->id,
            'status' => 'Pending',
        ]);

        $this->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('Sent', $notification->delivery_status);

        Http::assertSent(function ($request) {
            return $request->url() === 'https://api.semaphore.co/api/v4/messages'
                && $request['apikey'] === 'test-key'
                && $request['number'] === '09171234567'
                && $request['sendername'] === 'RHUCONNECT';
        });
    }

    public function test_real_gateway_send_marks_notification_failed_on_error_response(): void
    {
        config(['services.semaphore.api_key' => 'test-key']);
        Http::fake(['api.semaphore.co/*' => Http::response(['message' => 'Invalid API key'], 401)]);

        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'Pending']);

        $this->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('Failed', $notification->delivery_status);
    }

    public function test_real_gateway_send_marks_notification_failed_when_the_request_throws_without_breaking_the_status_update(): void
    {
        config(['services.semaphore.api_key' => 'test-key']);
        Http::fake(function () {
            throw new \Illuminate\Http\Client\ConnectionException('Connection timed out');
        });

        $doctor = $this->userWithRole('Doctor');
        $appointment = Appointment::factory()->create(['doctor_id' => $doctor->id, 'status' => 'Pending']);

        // The status update itself must succeed even though the SMS gateway
        // is unreachable — a down provider must never block appointment
        // workflow, only the notification's own delivery_status reflects it.
        $this->actingAs($this->userWithRole('Administrator'))
            ->patch(route('appointments.status', $appointment), ['status' => 'Approved'])
            ->assertSessionHasNoErrors();

        $appointment->refresh();
        $this->assertSame('Approved', $appointment->status);

        $notification = SmsNotification::query()->where('appointment_id', $appointment->id)->firstOrFail();
        $this->assertSame('Failed', $notification->delivery_status);
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
