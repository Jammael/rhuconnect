<?php

namespace Tests\Feature;

use App\Models\DoctorAvailabilityException;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Role;
use App\Models\User;
use App\Services\DoctorAvailabilityService;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DoctorAvailabilityTest extends TestCase
{
    use RefreshDatabase;

    public function test_doctor_can_view_and_update_own_template(): void
    {
        $doctor = $this->userWithRole('Doctor');

        // Can access index and gets redirected to own edit
        $this->actingAs($doctor)
            ->get('/doctor-availability')
            ->assertRedirect(route('doctor-availability.edit', $doctor));

        // Can access own edit page
        $this->actingAs($doctor)
            ->get(route('doctor-availability.edit', $doctor))
            ->assertOk()
            ->assertSee('My Availability Schedule')
            ->assertSee('Weekly Shift Template');

        // Can update weekly template schedule
        $daysData = [];
        for ($i = 0; $i < 7; $i++) {
            $daysData[$i] = [
                'is_active' => in_array($i, [1, 2, 3, 4, 5], true) ? '1' : '0',
                'start_time' => '08:00',
                'end_time' => '17:00',
            ];
        }

        $response = $this->actingAs($doctor)
            ->put(route('doctor-availability.update', $doctor), [
                'slot_duration_minutes' => 30,
                'days' => $daysData,
            ]);

        $response->assertRedirect(route('doctor-availability.edit', $doctor))
            ->assertSessionHas('status');

        $this->assertDatabaseHas('doctor_availability_templates', [
            'doctor_id' => $doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('doctor_availability_templates', [
            'doctor_id' => $doctor->id,
            'day_of_week' => 0, // Sunday
            'is_active' => false,
        ]);
    }

    public function test_doctor_cannot_view_or_update_another_doctors_template(): void
    {
        $doctor1 = $this->userWithRole('Doctor');
        $doctor2 = $this->userWithRole('Doctor');

        // Doctor 1 cannot edit Doctor 2's schedule
        $this->actingAs($doctor1)
            ->get(route('doctor-availability.edit', $doctor2))
            ->assertForbidden();

        // Doctor 1 cannot update Doctor 2's schedule
        $daysData = [];
        for ($i = 0; $i < 7; $i++) {
            $daysData[$i] = [
                'is_active' => '1',
                'start_time' => '08:00',
                'end_time' => '17:00',
            ];
        }

        $this->actingAs($doctor1)
            ->put(route('doctor-availability.update', $doctor2), [
                'slot_duration_minutes' => 30,
                'days' => $daysData,
            ])
            ->assertForbidden();

        // Doctor 1 cannot access Doctor 2's exceptions
        $this->actingAs($doctor1)
            ->get(route('doctor-availability.exceptions', $doctor2))
            ->assertForbidden();
    }

    public function test_doctor_can_manage_own_date_exceptions(): void
    {
        $doctor = $this->userWithRole('Doctor');

        // View exceptions page
        $this->actingAs($doctor)
            ->get(route('doctor-availability.exceptions', $doctor))
            ->assertOk()
            ->assertSee('Recorded Exceptions');

        // Add a leave exception (unavailable)
        $leaveDate = now()->addDays(5)->format('Y-m-d');
        $this->actingAs($doctor)
            ->post(route('doctor-availability.exceptions.store', $doctor), [
                'date' => $leaveDate,
                'is_available' => '0',
                'reason' => 'Annual Medical Conference',
            ])
            ->assertRedirect(route('doctor-availability.exceptions', $doctor))
            ->assertSessionHas('status');

        $exception = DoctorAvailabilityException::where('doctor_id', $doctor->id)->firstOrFail();
        $this->assertSame($leaveDate, $exception->date->format('Y-m-d'));
        $this->assertFalse($exception->is_available);
        $this->assertSame('Annual Medical Conference', $exception->reason);

        // Delete exception
        $this->actingAs($doctor)
            ->delete(route('doctor-availability.exceptions.destroy', [$doctor, $exception]))
            ->assertRedirect(route('doctor-availability.exceptions', $doctor));

        $this->assertDatabaseMissing('doctor_availability_exceptions', [
            'id' => $exception->id,
        ]);
    }

    public function test_administrator_can_view_and_manage_any_doctors_availability(): void
    {
        $admin = $this->userWithRole('Administrator');
        $doctor = $this->userWithRole('Doctor');

        // Admin can view index listing doctors
        $this->actingAs($admin)
            ->get('/doctor-availability')
            ->assertOk()
            ->assertSee('Doctor Availability')
            ->assertSee($doctor->name);

        // Admin can edit doctor's schedule
        $this->actingAs($admin)
            ->get(route('doctor-availability.edit', $doctor))
            ->assertOk()
            ->assertSee("Availability: {$doctor->name}");

        // Admin can view and add exception for doctor
        $customDate = now()->addDays(3)->format('Y-m-d');
        $this->actingAs($admin)
            ->post(route('doctor-availability.exceptions.store', $doctor), [
                'date' => $customDate,
                'is_available' => '1',
                'start_time' => '09:00',
                'end_time' => '13:00',
                'reason' => 'Special morning clinic',
            ])
            ->assertRedirect(route('doctor-availability.exceptions', $doctor));

        $exception = DoctorAvailabilityException::where('doctor_id', $doctor->id)->firstOrFail();
        $this->assertSame($customDate, $exception->date->format('Y-m-d'));
        $this->assertTrue($exception->is_available);
        $this->assertSame('09:00:00', $exception->start_time);
        $this->assertSame('13:00:00', $exception->end_time);
        $this->assertSame('Special morning clinic', $exception->reason);
    }

    public function test_slot_generation_computes_correct_slots(): void
    {
        $doctor = $this->userWithRole('Doctor');
        $service = app(DoctorAvailabilityService::class);

        // Setup Monday template: 08:00 - 10:00, 30 min duration
        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 1, // Monday
            'start_time' => '08:00:00',
            'end_time' => '10:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);

        // Setup Tuesday template: Inactive
        DoctorAvailabilityTemplate::create([
            'doctor_id' => $doctor->id,
            'day_of_week' => 2, // Tuesday
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => false,
        ]);

        // Find next Monday
        $nextMonday = Carbon::now()->next(Carbon::MONDAY);
        $slots = $service->getAvailableSlots($doctor, $nextMonday);

        // Expect: 08:00, 08:30, 09:00, 09:30
        $this->assertSame(['08:00', '08:30', '09:00', '09:30'], $slots);

        // Test helper on User model returns identical slots
        $this->assertSame($slots, $doctor->getAvailableSlots($nextMonday));

        // Find next Tuesday (Inactive day) -> empty slots
        $nextTuesday = Carbon::now()->next(Carbon::TUESDAY);
        $tuesdaySlots = $service->getAvailableSlots($doctor, $nextTuesday);
        $this->assertEmpty($tuesdaySlots);

        // Add Leave Exception on next Monday -> empty slots
        DoctorAvailabilityException::create([
            'doctor_id' => $doctor->id,
            'date' => $nextMonday->format('Y-m-d'),
            'is_available' => false,
            'reason' => 'Emergency leave',
        ]);

        $slotsOnLeave = $service->getAvailableSlots($doctor, $nextMonday);
        $this->assertEmpty($slotsOnLeave);

        // Add Custom Hours Exception on next Tuesday: 13:00 - 15:00 with 60 min slots
        DoctorAvailabilityException::create([
            'doctor_id' => $doctor->id,
            'date' => $nextTuesday->format('Y-m-d'),
            'is_available' => true,
            'start_time' => '13:00:00',
            'end_time' => '15:00:00',
            'reason' => 'Afternoon special shift',
        ]);

        $customSlots = $service->getAvailableSlots($doctor, $nextTuesday);
        // Default 30min slots: 13:00, 13:30, 14:00, 14:30
        $this->assertSame(['13:00', '13:30', '14:00', '14:30'], $customSlots);
    }

    private function userWithRole(string $roleName, array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role_id' => Role::firstOrCreate(['name' => $roleName])->id,
            'account_status' => 'ACTIVE',
        ], $attributes));
    }
}

