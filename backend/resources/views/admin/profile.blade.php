@php
    $theme = App\Models\Setting::get('admin_theme', 'gold');
    
    // Set theme classes
    $themeClasses = [
        'gold' => [
            'active' => 'bg-gold-500 text-slate-950',
            'accent' => 'gold-500'
        ],
        'blue' => [
            'active' => 'bg-blue-600 text-white shadow-md shadow-blue-500/20',
            'accent' => 'blue-600'
        ],
        'crimson' => [
            'active' => 'bg-crimson-600 text-white shadow-md shadow-crimson-500/20',
            'accent' => 'crimson-600'
        ],
        'emerald' => [
            'active' => 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20',
            'accent' => 'emerald-600'
        ]
    ];
    
    $currentTheme = $themeClasses[$theme] ?? $themeClasses['gold'];
@endphp
@extends('layouts.admin')

@section('title', 'Admin Profile | Admin Console')

@section('content')
<div class="space-y-8 select-none text-xs font-semibold">
    
    <!-- Header -->
    <div>
        <h2 class="text-xl font-extrabold text-slate-900 tracking-tight">Admin Profile Settings</h2>
        <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-normal font-semibold font-sans mt-2">Update security settings and master credentials for the management console</p>
    </div>

    <!-- Error blocks -->
    @if(isset($errors) && $errors->any())
    <div class="bg-crimson-50 border border-crimson-200 text-crimson-750 p-4 rounded-2xl text-xs space-y-1 shadow-sm font-semibold">
        <strong class="block font-bold"><i class="fa-solid fa-circle-exclamation mr-1 text-crimson-650"></i> Please correct the following errors:</strong>
        <ul class="list-disc pl-4 space-y-0.5">
            @foreach($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
    </div>
    @endif

    <!-- Form block -->
    <form action="{{ route('admin.profile.update') }}" method="POST" class="grid grid-cols-1 lg:grid-cols-2 gap-8 text-xs font-semibold">
        @csrf
        
        <!-- Left: Change Password Form -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm space-y-6">
            <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-3 flex items-center gap-1.5"><i class="fa-solid fa-key text-{{ $currentTheme['accent'] }}"></i> Change Admin Password</h3>
            
            <div class="space-y-1.5" x-data="{ show: false }">
                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1 px-0.5">Current Console Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="current_password" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-800 focus:outline-none transition-all font-mono">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-650 transition-colors focus:outline-none">
                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5" x-data="{ show: false }">
                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1 px-0.5">New Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-800 focus:outline-none transition-all font-mono">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-650 transition-colors focus:outline-none">
                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="space-y-1.5" x-data="{ show: false }">
                <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1 px-0.5">Confirm New Password</label>
                <div class="relative">
                    <input :type="show ? 'text' : 'password'" name="password_confirmation" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 focus:border-{{ $currentTheme['accent'] }} focus:bg-white rounded-xl pl-3.5 pr-10 py-2.5 text-xs text-slate-800 focus:outline-none transition-all font-mono">
                    <button type="button" @click="show = !show" class="absolute inset-y-0 right-0 flex items-center pr-3 text-slate-400 hover:text-slate-650 transition-colors focus:outline-none">
                        <i class="fa-solid" :class="show ? 'fa-eye-slash' : 'fa-eye'"></i>
                    </button>
                </div>
            </div>

            <div class="pt-4">
                <button type="submit" class="w-full bg-gradient-to-r from-{{ $currentTheme['accent'] }} to-{{ $currentTheme['accent'] }}/90 hover:opacity-95 text-white font-extrabold py-3.5 rounded-full text-xs uppercase tracking-wider shadow transform active:scale-95 transition-all flex items-center justify-center gap-1.5">
                    <i class="fa-solid fa-shield-halved text-[11px]"></i>
                    <span>Update Admin Password</span>
                </button>
            </div>
        </div>

        <!-- Right: Security & Context -->
        <div class="bg-white border border-slate-200 rounded-2xl p-6 shadow-sm flex flex-col justify-between gap-6">
            <div class="space-y-6">
                <h3 class="text-xs font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-3 flex items-center gap-1.5"><i class="fa-solid fa-circle-info text-{{ $currentTheme['accent'] }}"></i> Profile & Security Console</h3>
                
                <div class="bg-slate-50 border border-slate-100 rounded-xl p-4 space-y-3.5">
                    <div class="flex gap-3">
                        <div class="text-[14px] text-{{ $currentTheme['accent'] }} pt-0.5">
                            <i class="fa-solid fa-lock"></i>
                        </div>
                        <div>
                            <h4 class="text-slate-800 font-bold text-[10px] uppercase tracking-wider">Single Master Password</h4>
                            <p class="text-slate-500 text-[9px] leading-normal font-medium mt-1">This console operates under a single master authentication credential. Changing this password updates the access key for the entire admin control panel.</p>
                        </div>
                    </div>
                    
                    <div class="flex gap-3 border-t border-slate-200/60 pt-3.5">
                        <div class="text-[14px] text-{{ $currentTheme['accent'] }} pt-0.5">
                            <i class="fa-solid fa-database"></i>
                        </div>
                        <div>
                            <h4 class="text-slate-800 font-bold text-[10px] uppercase tracking-wider">Persistence Layer</h4>
                            <p class="text-slate-500 text-[9px] leading-normal font-medium mt-1">Your new password is encrypted with bcrypt and stored safely in the database. Changing the password here overrides the default environment configuration (`ADMIN_PASSWORD` key inside your `.env` file).</p>
                        </div>
                    </div>

                    <div class="flex gap-3 border-t border-slate-200/60 pt-3.5">
                        <div class="text-[14px] text-amber-500 pt-0.5">
                            <i class="fa-solid fa-triangle-exclamation"></i>
                        </div>
                        <div>
                            <h4 class="text-amber-800 font-bold text-[10px] uppercase tracking-wider">Session Lifecycle</h4>
                            <p class="text-slate-500 text-[9px] leading-normal font-medium mt-1">Upon updating the password successfully, you will remain logged in for the current session. Make sure to note down or remember the new credentials for future logins.</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Decorative system details or stats -->
            <div class="border-t border-slate-100 pt-4 flex justify-between items-center text-[9px] text-slate-450 uppercase tracking-widest font-semibold px-1">
                <span>System Status: <span class="text-emerald-500">Secure</span></span>
                <span>Active Theme: <span class="text-{{ $currentTheme['accent'] }}">{{ $theme }}</span></span>
            </div>
        </div>

    </form>
</div>
@endsection
