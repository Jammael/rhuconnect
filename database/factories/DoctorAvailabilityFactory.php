<?php

namespace Database\Factories;

use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class DoctorAvailabilityFactory extends Factory
{
    public function definition(): array
    {
        return [
            'doctor_id' => User::factory(),
            'available_date' => fake()->dateTimeBetween('now', '+60 days')->format('Y-m-d'),
            'start_time' => '08:00:00',
            'end_time' => '17:00:00',
            'status' => fake()->randomElement(['Available', 'Unavailable']),
        ];
    }
}
