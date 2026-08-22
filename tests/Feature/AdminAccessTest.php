<?php

namespace Tests\Feature;

use App\Models\DoctorAvailabilityException;
use App\Models\DoctorAvailabilityTemplate;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    public function test_administrator_can_access_admin_dashboard(): void
    {
        $administratorRole = Role::factory()->administrator()->create();
        $administrator = User::factory()->create([
            'role_id' => $administratorRole->id,
        ]);

        $response = $this
            ->actingAs($administrator)
            ->get('/admin/dashboard');

        $response->assertOk();
    }

    public function test_non_administrator_cannot_access_admin_dashboard(): void
    {
        $doctor = User::factory()->create();

        $response = $this
            ->actingAs($doctor)
            ->get('/admin/dashboard');

        $response->assertForbidden();
    }

    public function test_admin_dashboard_uses_real_doctor_availability(): void
    {
        $administrator = User::factory()->create([
            'role_id' => Role::factory()->administrator()->create()->id,
        ]);
        $doctorRole = Role::firstOrCreate(['name' => 'Doctor']);
        $availableDoctor = User::factory()->create([
            'name' => 'Lenebeth Ebo',
            'role_id' => $doctorRole->id,
        ]);
        $unavailableDoctor = User::factory()->create([
            'name' => 'Another Doctor',
            'role_id' => $doctorRole->id,
        ]);

        DoctorAvailabilityTemplate::create([
            'doctor_id' => $availableDoctor->id,
            'day_of_week' => now()->dayOfWeek,
            'start_time' => '08:00:00',
            'end_time' => '12:00:00',
            'slot_duration_minutes' => 30,
            'is_active' => true,
        ]);
        DoctorAvailabilityException::create([
            'doctor_id' => $unavailableDoctor->id,
            'date' => today(),
            'is_available' => false,
        ]);

        $this->actingAs($administrator)
            ->get('/admin/dashboard')
            ->assertOk()
            ->assertSeeInOrder(['Available Doctors', '1', 'Currently available'])
            ->assertSee('Lenebeth Ebo')
            ->assertSee('Available for appointments today')
            ->assertSee('Another Doctor')
            ->assertSee('No availability scheduled today')
            ->assertDontSee('Dr. Elena Cruz')
            ->assertDontSee('Dr. Marco Lim');
    }
}
