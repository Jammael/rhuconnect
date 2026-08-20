<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;

class RoleSeeder extends Seeder
{
    public function run(): void
    {
        $roles = [
            'Administrator',
            'Doctor',
            'Nurse',
            'Midwife',
            'Data Encoder',
        ];

        foreach ($roles as $role) {
            Role::updateOrCreate([
                'name' => $role,
            ]);
        }
    }
}
