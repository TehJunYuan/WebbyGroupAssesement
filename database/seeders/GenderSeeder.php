<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Gender;

class GenderSeeder extends Seeder
{
    public function run(): void
    {
        $genders = [
            [
                'name' => 'Male',
                'Description' => 'Male gender',
                'InsertAt' => now(),
                'IsActive' => 1,
            ],
            [
                'name' => 'Female',
                'Description' => 'Female gender',
                'InsertAt' => now(),
                'IsActive' => 1,
            ],
            [
                'name' => 'Other',
                'Description' => 'Other gender',
                'InsertAt' => now(),
                'IsActive' => 1,
            ],
        ];

        foreach ($genders as $gender) {
            Gender::create($gender);
        }

        $this->command->info('Genders seeded successfully!');
    }
}

