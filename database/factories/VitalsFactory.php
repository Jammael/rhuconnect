<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class VitalsFactory extends Factory
{
    public function definition(): array
    {
        return [
            'appointment_id' => Appointment::factory(),
            'recorded_by' => User::factory(),
            'blood_pressure_systolic' => fake()->numberBetween(100, 140),
            'blood_pressure_diastolic' => fake()->numberBetween(60, 90),
            'temperature_celsius' => fake()->randomFloat(1, 36.1, 37.5),
            'pulse_rate' => fake()->numberBetween(60, 100),
            'respiratory_rate' => fake()->numberBetween(12, 20),
            'oxygen_saturation' => fake()->numberBetween(95, 100),
            'weight_kg' => fake()->randomFloat(2, 40, 90),
            'height_cm' => fake()->randomFloat(1, 140, 190),
            'chief_complaint' => fake()->optional()->sentence(),
        ];
    }
}
