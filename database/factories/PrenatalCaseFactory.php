<?php

namespace Database\Factories;

use App\Models\Patient;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

class PrenatalCaseFactory extends Factory
{
    public function definition(): array
    {
        $lmp = fake()->dateTimeBetween('-8 months', '-1 week');

        return [
            'patient_id' => Patient::factory(),
            'midwife_id' => User::factory(),
            'last_menstrual_period' => $lmp,
            'estimated_due_date' => (clone $lmp)->modify('+280 days'),
            'gravida' => fake()->numberBetween(1, 5),
            'para' => fake()->numberBetween(0, 4),
            'is_high_risk' => false,
            'risk_notes' => null,
            'status' => 'Active',
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
