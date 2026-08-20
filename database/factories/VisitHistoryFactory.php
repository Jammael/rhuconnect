<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VisitHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'doctor_id' => User::factory(),
            'diagnosis' => fake()->sentence(6),
            'prescription' => fake()->optional()->paragraph(),
            'notes' => fake()->optional()->paragraph(),
            'consultation_date' => fake()->dateTimeBetween('-1 year', 'now')->format('Y-m-d'),
        ];
    }
}
