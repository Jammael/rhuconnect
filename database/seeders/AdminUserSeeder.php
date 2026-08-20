<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $name = config('rhuconnect.admin.name');
        $email = config('rhuconnect.admin.email');
        $password = config('rhuconnect.admin.password');

        if (! $name || ! $email || ! $password || $password === 'change-me') {
            throw new RuntimeException('Set RHU_ADMIN_NAME, RHU_ADMIN_EMAIL, and RHU_ADMIN_PASSWORD in .env before seeding the first administrator.');
        }

        $administratorRole = Role::firstOrCreate([
            'name' => 'Administrator',
        ]);

        $admin = User::firstOrNew([
            'email' => $email,
        ]);

        $admin->forceFill([
            'role_id' => $administratorRole->id,
            'name' => $name,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'account_status' => 'ACTIVE',
        ]);

        if (! $admin->exists) {
            $admin->forceFill([
                'password' => Hash::make($password),
                'remember_token' => Str::random(60),
            ]);
        }

        $admin->save();
    }
}
