<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class AppointmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'doctor_id' => User::factory(),
            'appointment_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'appointment_time' => fake()->time('H:i:s'),
            'purpose_of_visit' => fake()->sentence(4),
            'status' => fake()->randomElement(['Pending', 'Approved', 'Cancelled', 'Completed', 'No Show']),
            'remarks' => fake()->optional()->sentence(),
        ];
    }
}
