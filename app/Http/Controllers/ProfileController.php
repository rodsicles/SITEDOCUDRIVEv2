<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;

class ProfileController extends Controller
{
    public function edit()
    {
        $user = auth()->user();
        $employee = $user->employee;
        
        return view('profile.edit', compact('user', 'employee'));
    }

    public function update(Request $request)
    {
        $user = auth()->user();
        $employee = $user->employee;

        $validated = $request->validate([
            'full_name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email,' . $user->id,
            'employee_no' => 'nullable|string|unique:employees,employee_no,' . $employee->employee_id . ',employee_id',
            'department' => 'nullable|string|max:100',
        ]);

        $user->update([
            'email' => $validated['email'],
        ]);

        $employee->update([
            'full_name' => $validated['full_name'],
            'employee_no' => $validated['employee_no'],
            'department' => $validated['department'],
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

        return redirect()->back()->with('success', 'Password changed successfully');
    }
}
