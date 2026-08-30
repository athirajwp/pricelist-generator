<?php

namespace App\Http\Controllers\AdminSys;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show super admin profile edit screen.
     */
    public function edit()
    {
        $username = Setting::get('super_admin_username');
        if (!$username) {
            $username = env('SUPER_ADMIN_USERNAME', 'superadmin');
        }

        return view('admin_sys.profile', compact('username'));
    }

    /**
     * Update super admin username and password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'username' => 'required|string|max:255',
            'current_password' => 'required|string',
            'password' => 'nullable|string|min:6|confirmed',
        ]);

        // Fetch current active password for verification
        $currentActivePassword = Setting::get('super_admin_password');
        if (!$currentActivePassword) {
            $currentActivePassword = env('SUPER_ADMIN_PASSWORD', 'superadmin123');
        }

        // Verify current password match (handles both bcrypt hashes and raw text)
        $isMatch = false;
        if (str_starts_with($currentActivePassword, '$2y$') || str_starts_with($currentActivePassword, '$2a$')) {
            $isMatch = Hash::check($request->current_password, $currentActivePassword);
        } else {
            $isMatch = ($request->current_password === $currentActivePassword);
        }

        if (!$isMatch) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        // Save new username
        Setting::set('super_admin_username', $request->username, 'text');

        // Save new password if provided
        if ($request->filled('password')) {
            Setting::set('super_admin_password', Hash::make($request->password), 'text');
        }

        return redirect()->route('admin_sys.profile')->with('success', 'Super Admin profile details updated successfully!');
    }
}
