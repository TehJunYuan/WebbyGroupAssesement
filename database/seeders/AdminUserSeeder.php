<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::withoutGlobalScope(\App\Models\Scopes\ActiveScope::class)
            ->where('name', 'Admin')
            ->first();

        if (!$adminRole) {
            $this->command->warn('Admin role not found. Please run PermissionsSeeder first.');
            return;
        }

        $admin = User::firstOrCreate(
            ['email' => 'admin@example.com'],
            [
                'name' => 'Administrator',
                'password' => 'password',
                'email_verified_at' => now(),
            ]
        );

        if (!$admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
            $this->command->info('Admin user created and assigned Admin role successfully!');
        } else {
            $this->command->info('Admin user already exists with Admin role.');
        }
    }
}
