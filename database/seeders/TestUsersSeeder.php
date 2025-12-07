<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Role;
use Illuminate\Database\Seeder;

class TestUsersSeeder extends Seeder
{
    public function run(): void
    {
        $sellerRole = Role::withoutGlobalScope(\App\Models\Scopes\ActiveScope::class)
            ->where('name', 'Seller')
            ->first();

        if (!$sellerRole) {
            $this->command->warn('Seller role not found. Please run PermissionsSeeder first.');
            return;
        }

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

        for ($i = 1; $i <= 10; $i++) {
            $seller = User::firstOrCreate(
                ['email' => "seller{$i}@example.com"],
                [
                    'name' => "Seller {$i}",
                    'password' => 'password',
                    'email_verified_at' => now(),
                    'seller_approval_status' => 1,
                    'seller_approved_at' => now(),
                    'seller_approved_by' => $admin->id,
                    'seller_applied_at' => now()->subDays(rand(1, 30)),
                ]
            );

            if (!$seller->hasRole('Seller')) {
                $seller->assignRole('Seller');
            }

            $this->command->info("Seller {$i} created: seller{$i}@example.com (password: password)");
        }

        for ($i = 1; $i <= 10; $i++) {
            $user = User::firstOrCreate(
                ['email' => "user{$i}@example.com"],
                [
                    'name' => "User {$i}",
                    'password' => 'password',
                    'email_verified_at' => now(),
                ]
            );

            if ($user->roles()->exists()) {
                $user->roles()->detach();
            }

            $this->command->info("User {$i} created: user{$i}@example.com (password: password)");
        }

        $this->command->info('All test users created successfully!');
        $this->command->info('Summary:');
        $this->command->info('- 1 Admin account: admin@example.com');
        $this->command->info('- 10 Seller accounts: seller1@example.com to seller10@example.com (all approved)');
        $this->command->info('- 10 Normal user accounts: user1@example.com to user10@example.com (no roles)');
        $this->command->info('All passwords: password');
    }
}

