<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use App\Models\DashboardLog;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canEditFullName = $user->isDean() || $user->isProgramCoordinator();

        return view('profile.edit', compact('user', 'employee', 'canEditFullName'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canEditFullName = $user->isDean() || $user->isProgramCoordinator();

        $rules = [
            // Email is optional (no SMTP wired up) and not enforced unique
            // because the system uses username for identity. Once SMTP lands
            // we may re-introduce uniqueness then.
            'email' => 'nullable|email|max:45',
            'employee_no' => 'nullable|string|max:15|regex:/^[0-9]*$/|unique:employees,employee_no,'.$employee->employee_id.',employee_id',
            'department' => 'nullable|in:Engineering,Information Technology',
        ];

        if ($canEditFullName) {
            $rules['full_name'] = 'required|string|max:45';
        }

        $validated = $request->validate($rules);

        $user->update([
            'email' => $validated['email'] ?: null,
        ]);

        $employeeData = [
            'employee_no' => $validated['employee_no'] ?? null,
            'department' => $validated['department'] ?? null,
        ];

        if ($canEditFullName) {
            $employeeData['full_name'] = $validated['full_name'];
        }

        $employee->update($employeeData);

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Updated own profile',
            'activity_type' => 'profile_update',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function changePassword(Request $request)
    {
        $validated = $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Password::min(8)],
        ]);

        if (!Hash::check($validated['current_password'], auth()->user()->password)) {
            return back()->withErrors(['current_password' => 'Current password is incorrect']);
        }

        auth()->user()->update([
            'password' => Hash::make($validated['new_password']),
        ]);

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Changed own password',
            'activity_type' => 'password_change',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', 'Password changed successfully');
    }
}
