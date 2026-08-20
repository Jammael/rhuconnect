<?php

namespace Database\Factories;

use App\Models\Appointment;
use Illuminate\Database\Eloquent\Factories\Factory;

class QueueFactory extends Factory
{
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'queue_number' => fake()->unique()->numerify('Q-####'),
            'priority_type' => fake()->randomElement(['Regular', 'Senior Citizen', 'PWD', 'Pregnant']),
            'queue_status' => fake()->randomElement(['Waiting', 'Serving', 'Completed', 'Skipped', 'Cancelled']),
        ];
    }
}
