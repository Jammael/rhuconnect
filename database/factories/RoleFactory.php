<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class RoleFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => 'Doctor',
        ];
    }

    public function administrator(): static
    {
        return $this->state(fn (array $attributes) => [
            'name' => 'Administrator',
        ]);
    }
}
