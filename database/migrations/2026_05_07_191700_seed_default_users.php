<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

return new class extends Migration
{
    public function up(): void
    {
        $accounts = [
            [
                'role_id'  => 1,
                'username' => 'dean',
                'name'     => 'Dean Administrator',
                'email'    => 'dean@example.com',
                'password' => Hash::make('password123'),
                'status'   => 'Active',
                'employee' => [
                    'employee_no' => 'DEAN001',
                    'full_name'   => 'Dr. John Dean',
                    'department'  => 'Engineering',
                    'position'    => 'Dean',
                    'hire_date'   => now()->subYears(5)->toDateString(),
                ],
            ],
            [
                'role_id'  => 2,
                'username' => 'coordinator',
                'name'     => 'Program Coordinator',
                'email'    => 'coordinator@example.com',
                'password' => Hash::make('password123'),
                'status'   => 'Active',
                'employee' => [
                    'employee_no' => 'COORD001',
                    'full_name'   => 'Jane Smith',
                    'department'  => 'Information Technology',
                    'position'    => 'Program Coordinator',
                    'hire_date'   => now()->subYears(3)->toDateString(),
                ],
            ],
            [
                'role_id'  => 3,
                'username' => 'faculty',
                'name'     => 'Faculty Member',
                'email'    => 'faculty@example.com',
                'password' => Hash::make('password123'),
                'status'   => 'Active',
                'employee' => [
                    'employee_no' => 'FAC001',
                    'full_name'   => 'Robert Johnson',
                    'department'  => 'Information Technology',
                    'position'    => 'Faculty Employee',
                    'hire_date'   => now()->subYears(2)->toDateString(),
                ],
            ],
            [
                'role_id'  => 4,
                'username' => 'secretary',
                'name'     => 'Secretary',
                'email'    => 'secretary@example.com',
                'password' => Hash::make('password123'),
                'status'   => 'Active',
                'employee' => [
                    'employee_no' => 'SEC001',
                    'full_name'   => 'Maria Secretary',
                    'department'  => 'Engineering',
                    'position'    => 'Secretary',
                    'hire_date'   => now()->subYears(1)->toDateString(),
                ],
            ],
        ];

        foreach ($accounts as $account) {
            $employee = $account['employee'];
            unset($account['employee']);

            $existing = DB::table('users')->where('username', $account['username'])->first();

            if (!$existing) {
                $now = now();
                $account['created_at'] = $now;
                $account['updated_at'] = $now;
                $userId = DB::table('users')->insertGetId($account);

                DB::table('employees')->insert(array_merge($employee, [
                    'user_id'    => $userId,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]));
            }
        }
    }

    public function down(): void
    {
        $usernames = ['dean', 'coordinator', 'faculty', 'secretary'];
        $userIds = DB::table('users')->whereIn('username', $usernames)->pluck('id');
        DB::table('employees')->whereIn('user_id', $userIds)->delete();
        DB::table('users')->whereIn('username', $usernames)->delete();
    }
};
