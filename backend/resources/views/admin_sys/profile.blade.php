@extends('layouts.admin_sys')

@php
    $theme = App\Models\Setting::get('admin_theme', 'gold');
    
    // Set theme classes for local rendering scope
    $themeClasses = [
        'gold' => [
            'accent' => 'gold-500',
            'accent_hover' => 'gold-650',
            'icon' => 'bg-gold-500 text-slate-950',
            'thead' => 'bg-gold-500 text-slate-950 shadow-sm shadow-gold-500/10'
        ],
        'blue' => [
            'accent' => 'blue-600',
            'accent_hover' => 'blue-750',
            'icon' => 'bg-blue-500 text-white',
            'thead' => 'bg-blue-600 text-white shadow-md shadow-blue-500/20'
        ],
        'crimson' => [
            'accent' => 'crimson-600',
            'accent_hover' => 'crimson-750',
            'icon' => 'bg-crimson-500 text-white',
            'thead' => 'bg-crimson-600 text-white shadow-md shadow-crimson-500/20'
        ],
        'emerald' => [
            'accent' => 'emerald-600',
            'accent_hover' => 'emerald-750',
            'icon' => 'bg-emerald-500 text-white',
            'thead' => 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20'
        ]
    ];
    
    $currentTheme = $themeClasses[$theme] ?? $themeClasses['gold'];
@endphp

@section('title', 'Super Admin Profile | System Console')

@section('content')
<div class="space-y-6 select-none text-xs font-semibold max-w-4xl mx-auto">
    
    <!-- Header -->
    <div>
        <h2 class="text-xl md:text-2xl font-black text-slate-800 tracking-tight leading-none">Super Admin Profile</h2>
        <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-normal font-semibold mt-2">Manage your Super Admin credentials and console security settings</p>
    </div>

    <!-- Main Profile Card -->
    <div class="bg-white border border-slate-200 rounded-3xl p-6 shadow-sm space-y-6">
        
        <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-3 flex items-center gap-1.5">
            <i class="fa-solid fa-user-shield text-{{ $currentTheme['accent'] }}"></i> Security Credentials
        </h3>
        
        <form action="{{ route('admin_sys.profile.update') }}" method="POST" class="space-y-6">
            @csrf
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <!-- Username -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-0.5">Super Admin Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                            <i class="fa-solid fa-user-shield text-xs"></i>
                        </span>
                        <input type="text" name="username" required value="{{ old('username', $username) }}" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs text-slate-800 focus:outline-none transition-all">
                    </div>
                </div>

                <!-- Current Password -->
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-0.5">Current Password <span class="text-crimson-600">*</span></label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                            <i class="fa-solid fa-key text-xs"></i>
                        </span>
                        <input type="password" name="current_password" required placeholder="Required to authorize changes" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs text-slate-800 focus:outline-none transition-all">
                    </div>
                </div>
            </div>

            <div class="border-t border-slate-100 pt-6">
                <h4 class="text-[10px] font-bold text-slate-400 uppercase tracking-wider mb-4">Update Password (Optional)</h4>
                
                <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                    <!-- New Password -->
                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-0.5">New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                            <input type="password" name="password" placeholder="Min. 6 characters (leave blank to keep current)" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs text-slate-800 focus:outline-none transition-all">
                        </div>
                    </div>

                    <!-- Confirm Password -->
                    <div class="space-y-1.5">
                        <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-0.5">Confirm New Password</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-450">
                                <i class="fa-solid fa-lock text-xs"></i>
                            </span>
                            <input type="password" name="password_confirmation" placeholder="Re-type new password" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl py-3 pl-10 pr-4 text-xs text-slate-800 focus:outline-none transition-all">
                        </div>
                    </div>
                </div>
            </div>

            <!-- Submit Button -->
            <div class="pt-4 flex justify-end">
                <button type="submit" class="bg-{{ $currentTheme['accent'] }} hover:bg-{{ $currentTheme['accent_hover'] }} text-{{ $theme === 'gold' ? 'slate-950' : 'white' }} font-extrabold text-xs uppercase tracking-wider px-6 py-3 rounded-xl transition-all shadow-md active:scale-95 flex items-center gap-1.5">
                    <i class="fa-solid fa-floppy-disk text-sm"></i>
                    <span>Save Profile Settings</span>
                </button>
            </div>

        </form>

    </div>

</div>
@endsection
