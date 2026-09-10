<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Production/Cloud: ensure a single Dean login exists after placeholder cleanup.
 * Department is Information Technology; role powers remain Dean (role_id for Dean).
 */
return new class extends Migration
{
    public function up(): void
    {
        $roleId = DB::table('roles')->where('role_name', 'Dean')->value('role_id');
        if (!$roleId) {
            return;
        }

        $existing = DB::table('users')->where('username', 'dean')->first();
        $now = now();
        $password = Hash::make('password123');

        if ($existing) {
            DB::table('users')->where('id', $existing->id)->update([
                'role_id' => $roleId,
                'name' => 'Dean Administrator',
                'status' => 'Active',
                'must_change_password' => false,
                'password' => $password,
                'updated_at' => $now,
            ]);

            DB::table('employees')->updateOrInsert(
                ['user_id' => $existing->id],
                [
                    'employee_no' => 'DEAN001',
                    'full_name' => 'Dr. John Dean',
                    'department' => 'Information Technology',
                    'position' => 'Dean',
                    'hire_date' => $now->copy()->subYears(5)->toDateString(),
                    'updated_at' => $now,
                    'created_at' => $now,
                ]
            );

            return;
        }

        $userId = DB::table('users')->insertGetId([
            'role_id' => $roleId,
            'username' => 'dean',
            'name' => 'Dean Administrator',
            'email' => 'dean@example.com',
            'password' => $password,
            'status' => 'Active',
            'must_change_password' => false,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('employees')->insert([
            'user_id' => $userId,
            'employee_no' => 'DEAN001',
            'full_name' => 'Dr. John Dean',
            'department' => 'Information Technology',
            'position' => 'Dean',
            'hire_date' => $now->copy()->subYears(5)->toDateString(),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        $userId = DB::table('users')->where('username', 'dean')->value('id');
        if (!$userId) {
            return;
        }

        DB::table('employees')->where('user_id', $userId)->delete();
        DB::table('users')->where('id', $userId)->delete();
    }
};
