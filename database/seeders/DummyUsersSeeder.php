<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

class DummyUsersSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $password = Hash::make('password'); // Common password

        // Create Managers
        $managers = [
            ['name' => 'Manager One', 'email' => 'manager1@example.com'],
            ['name' => 'Manager Two', 'email' => 'manager2@example.com'],
            ['name' => 'Manager Three', 'email' => 'manager3@example.com'],
            ['name' => 'Manager Four', 'email' => 'manager4@example.com'],
            ['name' => 'Manager Five', 'email' => 'manager5@example.com'],
        ];

        foreach ($managers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                ]
            );
            $user->assignRole('manager');
        }

        // Create HR Users
        $hrUsers = [
            ['name' => 'HR One', 'email' => 'hr1@example.com'],
            ['name' => 'HR Two', 'email' => 'hr2@example.com'],
        ];

        foreach ($hrUsers as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                ]
            );
            $user->assignRole('hr');
        }

        // Create Employees
        $employees = [
            ['name' => 'Employee One', 'email' => 'emp1@example.com'],
            ['name' => 'Employee Two', 'email' => 'emp2@example.com'],
        ];

        foreach ($employees as $data) {
            $user = User::firstOrCreate(
                ['email' => $data['email']],
                [
                    'name' => $data['name'],
                    'password' => $password,
                ]
            );
            $user->assignRole('employee'); // Assuming 'employee' role exists or just no role
        }

        $this->command->info('Dummy Managers and HR users seeded successfully.');
    }
}
