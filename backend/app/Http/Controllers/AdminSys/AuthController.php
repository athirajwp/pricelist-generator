<?php

namespace App\Http\Controllers\AdminSys;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Show super admin login screen.
     */
    public function showLogin()
    {
        if (session()->has('super_admin_logged_in')) {
            return redirect()->route('admin_sys.company.index');
        }
        return view('admin_sys.login');
    }

    /**
     * Handle login authentication.
     */
    public function login(Request $request)
    {
        $request->validate([
            'username' => 'required|string',
            'password' => 'required|string',
        ]);

        $expectedUser = \App\Models\Setting::get('super_admin_username');
        if (!$expectedUser) {
            $expectedUser = env('SUPER_ADMIN_USERNAME', 'superadmin');
        }

        $expectedPass = \App\Models\Setting::get('super_admin_password');
        if (!$expectedPass) {
            $expectedPass = env('SUPER_ADMIN_PASSWORD', 'superadmin123');
        }

        $isPasswordMatch = false;
        if (str_starts_with($expectedPass, '$2y$') || str_starts_with($expectedPass, '$2a$')) {
            $isPasswordMatch = \Illuminate\Support\Facades\Hash::check($request->password, $expectedPass);
        } else {
            $isPasswordMatch = ($request->password === $expectedPass);
        }

        if ($request->username === $expectedUser && $isPasswordMatch) {
            session(['super_admin_logged_in' => true]);
            return redirect()->route('admin_sys.company.index');
        }

        return back()->withErrors([
            'auth_failed' => 'Invalid username or password!',
        ])->withInput($request->only('username'));
    }

    /**
     * Log out super admin.
     */
    public function logout()
    {
        session()->forget('super_admin_logged_in');
        return redirect()->route('admin_sys.login');
    }
}
