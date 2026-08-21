<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

class PatientFactory extends Factory
{
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'middle_name' => fake()->optional()->firstName(),
            'last_name' => fake()->lastName(),
            'birthdate' => fake()->dateTimeBetween('-90 years', '-1 year')->format('Y-m-d'),
            'sex' => fake()->randomElement(['Male', 'Female']),
            'civil_status' => fake()->randomElement(['Single', 'Married', 'Widowed', 'Separated']),
            'address' => fake()->streetAddress(),
            'barangay' => fake()->randomElement([
                'Abachanan',
                'Anibongan',
                'Bugsoc',
                'Cahayag',
                'Canlangit',
                'Cantaub',
                'Dusita',
                'La Union',
                'Lataban',
                'Magsaysay',
                'Man-od',
                'Matin-ao',
                'Poblacion',
                'Salvador',
                'San Agustin',
                'San Isidro',
                'San Jose',
                'San Juan',
                'Santa Cruz',
                'Villa Garcia',
            ]),
            'contact_number' => '09' . fake()->numerify('#########'),
            'email' => fake()->optional()->safeEmail(),
            'philhealth_id' => fake()->optional()->numerify('##-#########-#'),
            'blood_type' => fake()->optional()->randomElement(['A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-']),
            'guardian_name' => fake()->optional()->name(),
            'guardian_contact' => fake()->optional()->numerify('09#########'),
            'known_allergies' => fake()->optional()->sentence(),
            'existing_conditions' => fake()->optional()->sentence(),
            'current_medications' => fake()->optional()->sentence(),
            'emergency_contact_name' => fake()->name(),
            'emergency_contact_number' => '09' . fake()->numerify('#########'),
        ];
    }
}
