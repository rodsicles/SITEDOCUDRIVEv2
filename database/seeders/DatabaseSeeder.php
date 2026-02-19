<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Employee;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Create Dean User
        $deanUser = User::create([
            'role_id' => 1,
            'username' => 'dean',
            'name' => 'Dean Administrator',
            'email' => 'dean@example.com',
            'password' => Hash::make('password123'),
            'status' => 'Active',
        ]);

        Employee::create([
            'user_id' => $deanUser->id,
            'employee_no' => 'DEAN001',
            'full_name' => 'Dr. John Dean',
            'department' => 'Engineering',
            'position' => 'Dean',
            'hire_date' => now()->subYears(5),
        ]);

        // Create Program Coordinator User
        $coordinatorUser = User::create([
            'role_id' => 2,
            'username' => 'coordinator',
            'name' => 'Program Coordinator',
            'email' => 'coordinator@example.com',
            'password' => Hash::make('password123'),
            'status' => 'Active',
        ]);

        Employee::create([
            'user_id' => $coordinatorUser->id,
            'employee_no' => 'COORD001',
            'full_name' => 'Jane Smith',
            'department' => 'Information Technology',
            'position' => 'Program Coordinator',
            'hire_date' => now()->subYears(3),
        ]);

        // Create Faculty User
        $facultyUser = User::create([
            'role_id' => 3,
            'username' => 'faculty',
            'name' => 'Faculty Member',
            'email' => 'faculty@example.com',
            'password' => Hash::make('password123'),
            'status' => 'Active',
        ]);

        Employee::create([
            'user_id' => $facultyUser->id,
            'employee_no' => 'FAC001',
            'full_name' => 'Robert Johnson',
            'department' => 'Information Technology',
            'position' => 'Faculty Employee',
            'hire_date' => now()->subYears(2),
        ]);

        $this->command->info('Sample users created successfully!');
        $this->command->info('Dean - Username: dean, Password: password123');
        $this->command->info('Coordinator - Username: coordinator, Password: password123');
        $this->command->info('Faculty - Username: faculty, Password: password123');
    }
}
