<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;
use App\Models\DashboardLog;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canEditFullName = $user->isDean() || $user->isProgramCoordinator();
        $canEditEmployeeNo = $user->isDean();

        return view('profile.edit', compact('user', 'employee', 'canEditFullName', 'canEditEmployeeNo'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;
        $canEditFullName = $user->isDean() || $user->isProgramCoordinator();
        $canEditEmployeeNo = $user->isDean();

        $rules = [
            // Email is optional (no SMTP wired up) and not enforced unique
            // because the system uses username for identity. Once SMTP lands
            // we may re-introduce uniqueness then.
            'email' => 'nullable|email|max:45',
        ];

        if ($canEditFullName) {
            $rules['full_name'] = 'required|string|max:45';
        }

        if ($canEditEmployeeNo) {
            $rules['employee_no'] = 'nullable|string|max:20|regex:/^[A-Za-z0-9-]+$/|unique:employees,employee_no,'.optional($employee)->employee_id.',employee_id';
        }

        $validated = $request->validate($rules, [
            'employee_no.regex' => 'Employee number may only contain letters, numbers, and dashes.',
        ]);

        $user->update([
            'email' => ($validated['email'] ?? null) ?: null,
        ]);

        // Program and (for non-Dean users) employee number are managed through employee management.
        $employeeData = [];

        if ($canEditEmployeeNo) {
            $employeeData['employee_no'] = $validated['employee_no'] ?? null;
        }

        if ($canEditFullName) {
            $employeeData['full_name'] = $validated['full_name'];
        }

        if ($employee && $employeeData) {
            $employee->update($employeeData);
        }

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Updated own profile',
            'activity_type' => 'profile_update',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', 'Profile updated successfully');
    }

    public function uploadAvatar(Request $request)
    {
        $request->validate([
            'avatar' => 'required|file|image|mimes:jpg,jpeg,png,webp|max:2048',
        ], [
            'avatar.required' => 'Please choose a photo to upload.',
            'avatar.image' => 'The file must be an image (JPG, PNG, or WebP).',
            'avatar.mimes' => 'Only JPG, PNG, and WebP images are allowed.',
            'avatar.max' => 'The photo must be 2MB or smaller.',
        ]);

        $user = auth()->user();

        if ($user->avatar_path) {
            Storage::disk('public')->delete($user->avatar_path);
        }

        $path = $request->file('avatar')->store('avatars', 'public');

        $user->update(['avatar_path' => $path]);

        DashboardLog::create([
            'user_id' => auth()->id(),
            'activity' => 'Updated profile picture',
            'activity_type' => 'profile_update',
            'visibility' => 'own',
        ]);

        return redirect()->back()->with('success', 'Profile picture updated successfully');
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
