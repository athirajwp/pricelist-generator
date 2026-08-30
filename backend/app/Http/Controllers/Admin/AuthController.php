<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuthController extends Controller
{
    /**
     * Show admin login screen.
     */
    public function showLogin()
    {
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';

        // Clear existing session to force password authentication
        session()->forget('admin_logged_in_' . $companyCode);

        return view('admin.login');
    }

    /**
     * Handle login authentication.
     */
    public function login(Request $request)
    {
        $request->validate([
            'password' => 'required|string',
        ]);

        // Fetch admin password from DB or fallback to env/default
        $adminPassword = \App\Models\Setting::get('admin_password');
        if (!$adminPassword) {
            $adminPassword = env('ADMIN_PASSWORD', 'admin123');
        }

        // Check if the stored password is bcrypt hashed
        $isMatch = false;
        if (str_starts_with($adminPassword, '$2y$') || str_starts_with($adminPassword, '$2a$')) {
            $isMatch = \Illuminate\Support\Facades\Hash::check($request->password, $adminPassword);
        } else {
            $isMatch = ($request->password === $adminPassword);
        }

        if ($isMatch) {
            $company = view()->shared('currentCompany');
            $companyCode = $company ? $company->code : 'default';
            session(['admin_logged_in_' . $companyCode => true]);
            // Use to() with a relative path to avoid APP_URL port mismatch
            // (e.g. when running on port 8001/8002, route() would redirect to APP_URL port 8000)
            return redirect()->to('/admin/dashboard');
        }

        return back()->withErrors(['password' => 'Invalid password!']);
    }

    /**
     * Log out admin and terminate session.
     */
    public function logout()
    {
        $company = view()->shared('currentCompany');
        $companyCode = $company ? $company->code : 'default';
        session()->forget('admin_logged_in_' . $companyCode);
        // Use to() with a relative path to avoid APP_URL port mismatch
        return redirect()->to('/admin/login?company=' . $companyCode);
    }
}
