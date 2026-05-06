<?php

namespace App\Services;

use App\Models\User;
use App\Models\Employee;
use App\Models\Task;
use App\Models\Document;
use App\Models\Folder;
use App\Models\Report;
use App\Models\Notification;
use App\Models\DashboardLog;
use App\Models\PerformanceReport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class EmployeeService
{
    /**
     * Create a coordinator account (user + employee) in a transaction.
     */
    public function createCoordinator(array $validated, int $creatorUserId): Employee
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'role_id' => 2,
                'name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'Active',
                // Force a password rotation on first login so the Dean is not
                // liable for whatever the temporary password is later used for.
                'must_change_password' => true,
                'password_changed_at'  => null,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_no' => $validated['employee_no'],
                'full_name' => $validated['full_name'],
                'department' => $validated['department'],
                'position' => 'Program Coordinator',
                'hire_date' => now(),
            ]);

            DashboardLog::create([
                'user_id' => $creatorUserId,
                'target_user_id' => $user->id,
                'activity' => 'Created coordinator account: ' . $validated['full_name'],
                'activity_type' => 'account_created',
                'visibility' => 'dean',
            ]);

            DB::commit();
            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Create a faculty account (user + employee) in a transaction.
     */
    public function createFaculty(array $validated, int $creatorUserId): Employee
    {
        DB::beginTransaction();
        try {
            $user = User::create([
                'role_id' => 3,
                'name' => $validated['full_name'],
                'username' => $validated['username'],
                'email' => $validated['email'],
                'password' => Hash::make($validated['password']),
                'status' => 'Active',
                // Force a password rotation on first login (chain-of-custody).
                'must_change_password' => true,
                'password_changed_at'  => null,
            ]);

            $employee = Employee::create([
                'user_id' => $user->id,
                'employee_no' => $validated['employee_no'],
                'full_name' => $validated['full_name'],
                'department' => $validated['department'],
                'position' => 'Faculty Employee',
                'hire_date' => now(),
            ]);

            DashboardLog::create([
                'user_id' => $creatorUserId,
                'target_user_id' => $user->id,
                'activity' => 'Created faculty account: ' . $validated['full_name'],
                'activity_type' => 'account_created',
                'visibility' => 'coordinator',
            ]);

            DB::commit();
            return $employee;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Update a faculty member's information in a transaction.
     */
    public function updateFaculty(Employee $employee, array $validated, int $updaterUserId): Employee
    {
        DB::beginTransaction();
        try {
            $employee->update([
                'full_name' => $validated['full_name'],
                'employee_no' => $validated['employee_no'],
                'department' => $validated['department'],
            ]);

            $employee->user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
            ]);

            DashboardLog::create([
                'user_id' => $updaterUserId,
                'target_user_id' => $employee->user_id,
                'activity' => 'Updated faculty information: ' . $validated['full_name'],
                'activity_type' => 'profile_update',
                'visibility' => 'coordinator',
            ]);

            DB::commit();
            return $employee->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reset a faculty member's password with notification and logging.
     */
    public function resetFacultyPassword(Employee $employee, string $newPassword, User $resetter): void
    {
        $employee->user->update([
            'password'             => Hash::make($newPassword),
            // Treat any admin reset like a fresh issuance: faculty must rotate again.
            'must_change_password' => true,
            'password_changed_at'  => null,
        ]);

        Notification::create([
            'user_id' => $employee->user_id,
            'message' => 'Your password has been reset by ' . $resetter->employee->full_name . ' (Program Coordinator). Please use your new password to login.',
        ]);

        DashboardLog::create([
            'user_id' => $resetter->id,
            'target_user_id' => $employee->user_id,
            'activity' => 'Reset password for faculty: ' . $employee->full_name,
            'activity_type' => 'password_reset',
            'visibility' => 'coordinator',
        ]);
    }

    /**
     * Update an employee's information (used by Dean).
     */
    public function updateEmployee(Employee $employee, array $validated, int $updaterUserId): Employee
    {
        DB::beginTransaction();
        try {
            $employee->update([
                'full_name' => $validated['full_name'],
                'employee_no' => $validated['employee_no'],
                'department' => $validated['department'],
            ]);

            $employee->user->update([
                'name' => $validated['full_name'],
                'email' => $validated['email'],
            ]);

            DashboardLog::create([
                'user_id' => $updaterUserId,
                'target_user_id' => $employee->user_id,
                'activity' => 'Updated employee information: ' . $validated['full_name'],
                'activity_type' => 'profile_update',
                'visibility' => 'dean',
            ]);

            DB::commit();
            return $employee->fresh();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Reset an employee's password (used by Dean).
     */
    public function resetEmployeePassword(Employee $employee, string $newPassword, User $resetter): void
    {
        $employee->user->update([
            'password'             => Hash::make($newPassword),
            // Treat any admin reset like a fresh issuance: target must rotate again.
            'must_change_password' => true,
            'password_changed_at'  => null,
        ]);

        Notification::create([
            'user_id' => $employee->user_id,
            'message' => 'Your password has been reset by ' . ($resetter->employee->full_name ?? 'Dean') . '. Please use your new password to login.',
        ]);

        DashboardLog::create([
            'user_id' => $resetter->id,
            'target_user_id' => $employee->user_id,
            'activity' => 'Reset password for: ' . $employee->full_name,
            'activity_type' => 'password_reset',
            'visibility' => 'dean',
        ]);
    }

    /**
     * Get employee profile data with all associated stats.
     */
    public function getEmployeeProfile(int $employeeId): array
    {
        $employee = Employee::with(['user.role', 'performanceReports.evaluator.employee'])
            ->where('employee_id', $employeeId)
            ->firstOrFail();

        $performanceReports = PerformanceReport::with('evaluator.employee')
            ->where('employee_id', $employeeId)
            ->orderBy('report_date', 'desc')
            ->get();

        $tasks = Task::with('assignedBy.employee')
            ->where('assigned_to', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $taskStats = [
            'total' => $tasks->count(),
            'completed' => $tasks->where('status', 'Completed')->count(),
            'pending' => $tasks->where('status', 'Pending')->count(),
        ];

        $documents = Document::select('document_id', 'uploaded_by', 'document_title', 'document_type', 'created_at')
            ->where('uploaded_by', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $documentStats = [
            'total' => $documents->count(),
            'byType' => $documents->groupBy('document_type')->map->count(),
        ];

        $folders = Cache::remember("employee_folders_{$employeeId}", now()->addMinutes(10), function () use ($employee) {
            return Folder::where('user_id', $employee->user_id)
                ->withCount('documents')
                ->orderBy('folder_name')
                ->get();
        });

        $reports = Report::select('report_id', 'submitted_by', 'report_category', 'created_at')
            ->where('submitted_by', $employee->user_id)
            ->orderBy('created_at', 'desc')
            ->get();

        $reportStats = [
            'total' => $reports->count(),
            'byCategory' => $reports->groupBy('report_category')->map->count(),
        ];

        return compact(
            'employee',
            'performanceReports',
            'tasks',
            'taskStats',
            'documents',
            'documentStats',
            'folders',
            'reports',
            'reportStats'
        );
    }

    /**
     * Get employee profile data visible to coordinator (no performance reports).
     */
    public function getEmployeeProfileForCoordinator(int $employeeId): array
    {
        $data = $this->getEmployeeProfile($employeeId);
        $data['performanceReports'] = collect(); // Coordinator cannot see performance reports

        return $data;
    }
}
