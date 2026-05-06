<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\DashboardLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    public function showRoleSelection()
    {
        if (Auth::check()) {
            $role = Auth::user()->role->role_name;
            return match($role) {
                'Dean'                => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee'    => redirect()->route('faculty.dashboard'),
                'Secretary'           => redirect()->route('dean.dashboard'),
                default               => redirect()->route('login'),
            };
        }

        return response()->view('auth.login')->withHeaders([
            'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            'Pragma'        => 'no-cache',
            'Expires'       => '0',
        ]);
    }

    public function showLogin()
    {
        if (Auth::check()) {
            $role = Auth::user()->role->role_name;
            return match($role) {
                'Dean'                => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee'    => redirect()->route('faculty.dashboard'),
                'Secretary'           => redirect()->route('dean.dashboard'),
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

        // Per-username + IP rate limiter (defence in depth alongside the
        // route-level IP throttle): 5 attempts / minute / (username + IP).
        $throttleKey = Str::lower($credentials['username']) . '|' . $request->ip();
        if (RateLimiter::tooManyAttempts($throttleKey, 5)) {
            $seconds = RateLimiter::availableIn($throttleKey);
            DashboardLog::create([
                'user_id'       => null,
                'activity'      => 'Login throttled for username: ' . $credentials['username'],
                'activity_type' => 'login_throttled',
                'visibility'    => 'dean',
            ]);
            throw ValidationException::withMessages([
                'username' => "Too many login attempts. Please try again in {$seconds} seconds.",
            ])->status(429);
        }

        if (Auth::attempt(['username' => $credentials['username'], 'password' => $credentials['password'], 'status' => 'Active'])) {
            RateLimiter::clear($throttleKey);
            $user = Auth::user();
            $role = $user->role->role_name;

            $request->session()->regenerate();

            DashboardLog::create([
                'user_id' => Auth::id(),
                'activity' => 'User logged in',
                'activity_type' => 'login',
            ]);

            return match($role) {
                'Dean' => redirect()->route('dean.dashboard'),
                'Program Coordinator' => redirect()->route('coordinator.dashboard'),
                'Faculty Employee' => redirect()->route('faculty.dashboard'),
                'Secretary' => redirect()->route('dean.dashboard'),
                default => redirect()->route('login'),
            };
        }

        // Count this failed attempt (decay 60s).
        RateLimiter::hit($throttleKey, 60);

        DashboardLog::create([
            'user_id' => null,
            'activity' => 'Failed login attempt for username: ' . $credentials['username'],
            'activity_type' => 'login_failed',
            'visibility' => 'dean',
        ]);

        return back()->withErrors([
            'username' => 'Invalid credentials.',
        ])->withInput($request->only('username'));
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
