<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DashboardLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

class AuthController extends Controller
{
    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'status' => 'Active'])) {
            $request->session()->regenerate();
            
            DashboardLog::create([
                'user_id' => Auth::id(),
                'activity' => 'User logged in',
            ]);

            $role = Auth::user()->role->role_name;
            
            return match($role) {
                'Dean' => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee' => redirect()->route('faculty.dashboard'),
                default => redirect()->route('login'),
            };
        }

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request)
    {
        DashboardLog::create([
            'user_id' => Auth::id(),
            'activity' => 'User logged out',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
