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
        $deanUser = User::firstOrCreate(
            ['username' => 'dean'],
            [
                'role_id' => 1,
                'name' => 'Dean Administrator',
                'email' => 'dean@example.com',
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ]
        );

        Employee::firstOrCreate(
            ['user_id' => $deanUser->id],
            [
                'employee_no' => 'DEAN001',
                'full_name' => 'Dr. John Dean',
                'department' => 'Engineering',
                'position' => 'Dean',
                'hire_date' => now()->subYears(5),
            ]
        );

        // Create Program Coordinator User
        $coordinatorUser = User::firstOrCreate(
            ['username' => 'coordinator'],
            [
                'role_id' => 2,
                'name' => 'Program Coordinator',
                'email' => 'coordinator@example.com',
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ]
        );

        Employee::firstOrCreate(
            ['user_id' => $coordinatorUser->id],
            [
                'employee_no' => 'COORD001',
                'full_name' => 'Jane Smith',
                'department' => 'Information Technology',
                'position' => 'Program Coordinator',
                'hire_date' => now()->subYears(3),
            ]
        );

        // Create Faculty User
        $facultyUser = User::firstOrCreate(
            ['username' => 'faculty'],
            [
                'role_id' => 3,
                'name' => 'Faculty Member',
                'email' => 'faculty@example.com',
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ]
        );

        Employee::firstOrCreate(
            ['user_id' => $facultyUser->id],
            [
                'employee_no' => 'FAC001',
                'full_name' => 'Robert Johnson',
                'department' => 'Information Technology',
                'position' => 'Faculty Employee',
                'hire_date' => now()->subYears(2),
            ]
        );

        // Create Secretary User
        $secretaryUser = User::firstOrCreate(
            ['username' => 'secretary'],
            [
                'role_id' => 4,
                'name' => 'Secretary',
                'email' => 'secretary@example.com',
                'password' => Hash::make('password123'),
                'status' => 'Active',
            ]
        );

        Employee::firstOrCreate(
            ['user_id' => $secretaryUser->id],
            [
                'employee_no' => 'SEC001',
                'full_name' => 'Maria Secretary',
                'department' => 'Engineering',
                'position' => 'Secretary',
                'hire_date' => now()->subYears(1),
            ]
        );

        $this->command->info('Sample users created successfully!');
        $this->command->info('Dean - Username: dean, Password: password123');
        $this->command->info('Coordinator - Username: coordinator, Password: password123');
        $this->command->info('Faculty - Username: faculty, Password: password123');
        $this->command->info('Secretary - Username: secretary, Password: password123');

        $this->call(SystemFolderSeeder::class);
    }
}
