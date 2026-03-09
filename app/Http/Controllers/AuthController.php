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
        if (Auth::check()) {
            $role = Auth::user()->role->role_name;
            return match($role) {
                'Dean'                => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee'    => redirect()->route('faculty.dashboard'),
                default               => redirect()->route('login'),
            };
        }

        return response()->view('auth.login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
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
                'activity_type' => 'login',
            ]);

            $role = Auth::user()->role->role_name;
            
            return match($role) {
                'Dean' => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee' => redirect()->route('faculty.dashboard'),
                default => redirect()->route('login'),
            };
        }

        DashboardLog::create([
            'user_id' => null,
            'activity' => 'Failed login attempt for username: ' . $credentials['username'],
            'activity_type' => 'login_failed',
            'visibility' => 'dean',
        ]);

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ]);
    }

    public function logout(Request $request)
    {
        DashboardLog::create([
            'user_id' => Auth::id(),
            'activity' => 'User logged out',
            'activity_type' => 'logout',
        ]);

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
