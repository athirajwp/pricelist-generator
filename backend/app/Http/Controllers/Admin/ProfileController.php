<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class ProfileController extends Controller
{
    /**
     * Show admin profile console.
     */
    public function edit()
    {
        return view('admin.profile');
    }

    /**
     * Update admin profile/password.
     */
    public function update(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'password' => 'required|string|min:6|confirmed|different:current_password',
        ], [
            'password.different' => 'The new password must be different from your current password.',
        ]);

        $currentActivePassword = Setting::get('admin_password');
        if (!$currentActivePassword) {
            $currentActivePassword = env('ADMIN_PASSWORD', 'admin123');
        }

        // Verify current password match
        $isMatch = false;
        if (str_starts_with($currentActivePassword, '$2y$') || str_starts_with($currentActivePassword, '$2a$')) {
            $isMatch = Hash::check($request->current_password, $currentActivePassword);
        } else {
            $isMatch = ($request->current_password === $currentActivePassword);
        }

        if (!$isMatch) {
            return back()->withErrors(['current_password' => 'The provided current password does not match our records.']);
        }

        // Store new password (bcrypt hashed)
        Setting::set('admin_password', Hash::make($request->password), 'text');

        return redirect()->route('admin.profile.edit')->with('success', 'Admin password updated successfully!');
    }
}
