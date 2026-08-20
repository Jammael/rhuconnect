<?php

namespace Database\Factories;

use App\Models\Appointment;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Factories\Factory;

class SmsNotificationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'patient_id' => Patient::factory(),
            'appointment_id' => Appointment::factory(),
            'message' => fake()->sentence(12),
            'recipient_number' => '09' . fake()->numerify('#########'),
            'delivery_status' => fake()->randomElement(['Pending', 'Sent', 'Failed']),
            'sent_at' => fake()->optional()->dateTimeBetween('-30 days', 'now'),
        ];
    }
}
