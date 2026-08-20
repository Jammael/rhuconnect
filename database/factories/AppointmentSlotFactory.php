<?php

namespace Database\Factories;

use App\Models\DoctorAvailability;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentSlotFactory extends Factory
{
    public function definition(): array
    {
        $maximumSlots = fake()->numberBetween(10, 40);

        return [
            'doctor_availability_id' => DoctorAvailability::factory(),
            'maximum_slots' => $maximumSlots,
            'booked_slots' => fake()->numberBetween(0, $maximumSlots),
        ];
    }
}
