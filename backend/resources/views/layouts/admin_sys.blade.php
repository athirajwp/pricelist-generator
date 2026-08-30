@php
    $theme = App\Models\Setting::get('admin_theme', 'gold');
    
    // Set theme classes
    $themeClasses = [
        'gold' => [
            'active' => 'bg-gold-500 text-slate-950',
            'icon' => 'bg-gold-500 text-slate-950',
            'border' => 'border-gold-500',
            'text' => 'text-gold-500',
            'accent' => 'gold-500',
            'accent_hover' => 'gold-650',
            'topbar' => 'bg-white border-b border-slate-250 text-slate-800 shadow-sm',
            'topbar_btn' => 'hover:bg-slate-100 text-slate-650',
            'topbar_badge' => 'text-slate-650 font-bold bg-slate-50 border border-slate-200',
            'sidebar_active' => 'bg-gold-50 text-gold-600 shadow-sm border border-gold-100',
            'logo_bg' => 'bg-gold-500 text-slate-950',
            'logo_border' => 'border-slate-200 bg-slate-50 text-slate-700',
            'text_accent' => 'text-gold-600',
            'thead' => 'bg-gold-500 text-slate-950'
        ],
        'blue' => [
            'active' => 'bg-blue-600 text-white shadow-md shadow-blue-500/20',
            'icon' => 'bg-blue-500 text-white',
            'border' => 'border-blue-600',
            'text' => 'text-blue-600',
            'accent' => 'blue-600',
            'accent_hover' => 'blue-750',
            'topbar' => 'bg-blue-600 border-b border-blue-700 text-white shadow-md',
            'topbar_btn' => 'hover:bg-blue-700/85 text-white',
            'topbar_badge' => 'text-white font-bold bg-blue-700 border border-blue-500',
            'sidebar_active' => 'bg-blue-50 text-blue-600 shadow-sm border border-blue-100',
            'logo_bg' => 'bg-white text-blue-600',
            'logo_border' => 'border-white/20 bg-white/10 text-white',
            'text_accent' => 'text-blue-600',
            'thead' => 'bg-blue-600 text-white'
        ],
        'crimson' => [
            'active' => 'bg-crimson-600 text-white shadow-md shadow-crimson-500/20',
            'icon' => 'bg-crimson-500 text-white',
            'border' => 'border-crimson-600',
            'text' => 'text-crimson-600',
            'accent' => 'crimson-600',
            'accent_hover' => 'crimson-750',
            'topbar' => 'bg-crimson-600 border-b border-crimson-700 text-white shadow-md',
            'topbar_btn' => 'hover:bg-crimson-700/85 text-white',
            'topbar_badge' => 'text-white font-bold bg-crimson-700 border border-crimson-500',
            'sidebar_active' => 'bg-crimson-50 text-crimson-600 shadow-sm border border-crimson-100',
            'logo_bg' => 'bg-white text-crimson-600',
            'logo_border' => 'border-white/20 bg-white/10 text-white',
            'text_accent' => 'text-crimson-600',
            'thead' => 'bg-crimson-600 text-white'
        ],
        'emerald' => [
            'active' => 'bg-emerald-600 text-white shadow-md shadow-emerald-500/20',
            'icon' => 'bg-emerald-500 text-white',
            'border' => 'border-emerald-600',
            'text' => 'text-emerald-600',
            'accent' => 'emerald-600',
            'accent_hover' => 'emerald-750',
            'topbar' => 'bg-emerald-600 border-b border-emerald-700 text-white shadow-md',
            'topbar_btn' => 'hover:bg-emerald-700/85 text-white',
            'topbar_badge' => 'text-white font-bold bg-emerald-700 border border-emerald-500',
            'sidebar_active' => 'bg-emerald-50 text-emerald-600 shadow-sm border border-emerald-100',
            'logo_bg' => 'bg-white text-emerald-600',
            'logo_border' => 'border-white/20 bg-white/10 text-white',
            'text_accent' => 'text-emerald-600',
            'thead' => 'bg-emerald-600 text-white'
        ]
    ];
    
    $currentTheme = $themeClasses[$theme] ?? $themeClasses['gold'];

    $superAdminUsername = App\Models\Setting::get('super_admin_username');
    if (!$superAdminUsername) {
        $superAdminUsername = env('SUPER_ADMIN_USERNAME', 'superadmin');
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Super Admin Console | Multi-Domain Controller')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/color/48/globe.png">
    
    <!-- Google Fonts -->
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700;800&family=Outfit:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- Tailwind CSS CDN -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Outfit', 'Poppins', 'sans-serif'],
                    },
                    colors: {
                        gold: {
                            50: '#fffdf0',
                            100: '#fef7c3',
                            200: '#fdf196',
                            300: '#fae459',
                            400: '#f8d82d',
                            500: '#e5bf13',
                            600: '#c2960b',
                            650: '#b88a09',
                            700: '#9b7009',
                            800: '#7d560c',
                            900: '#67460e',
                        },
                        crimson: {
                            50: '#fff1f1',
                            100: '#ffe1e1',
                            200: '#ffc7c7',
                            300: '#ffa0a0',
                            400: '#ff6969',
                            500: '#f83b3b',
                            600: '#e51d1d',
                            750: '#d01212',
                            700: '#c01212',
                            800: '#9f1313',
                            900: '#831616',
                        },
                        blue: {
                            50: '#eff6ff',
                            100: '#dbeafe',
                            200: '#bfdbfe',
                            300: '#93c5fd',
                            400: '#60a5fa',
                            500: '#3b82f6',
                            600: '#2563eb',
                            700: '#1d4ed8',
                            750: '#1a44c2',
                            800: '#1e40af',
                            900: '#1e3a8a',
                        },
                        emerald: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            750: '#03684a',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <!-- Alpine.js CDN -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Compatibility styles for legacy badge classes -->
    <style>
        .bg-danger { background-color: #e11d48 !important; }
        .bg-warning { background-color: #d97706 !important; }
        .bg-success { background-color: #059669 !important; }
        .bg-info { background-color: #0284c7 !important; }
        .bg-primary { background-color: #2563eb !important; }
        .bg-secondary { background-color: #475569 !important; }
        .text-dark { color: #0f172a !important; }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans select-none" x-data="{ sidebarOpen: window.innerWidth >= 768 }">

    <!-- 1. Top Navbar Header exactly matching screenshot -->
    <header class="{{ $currentTheme['topbar'] }} z-40 px-4 py-2.5 flex items-center justify-between sticky top-0 select-none">
        
        <!-- Left Section: Toggle Hamburger & Brand Logo -->
        <div class="flex items-center gap-4">
            <!-- Sidebar Toggler -->
            <button @click="sidebarOpen = !sidebarOpen" class="{{ $currentTheme['topbar_btn'] }} p-2 rounded-lg transition-colors focus:outline-none">
                <i class="fa-solid fa-bars text-lg"></i>
            </button>
            
            <!-- Brand Logo -->
            <a href="/" class="flex items-center gap-2 {{ $currentTheme['logo_border'] }} px-3 py-1.5 rounded-xl transition-all">
                <div class="w-7 h-7 rounded-lg flex items-center justify-center font-black text-sm shadow {{ $currentTheme['logo_bg'] }}">
                    C
                </div>
                <span class="font-extrabold text-sm tracking-wider uppercase">Crackers Sys</span>
            </a>
        </div>

        <!-- Right Section: Navigation utilities & Admin profile -->
        <div class="flex items-center gap-4 text-sm font-semibold select-none">
            <!-- Messages Icon -->
            <button class="{{ $currentTheme['topbar_btn'] }} p-2 rounded-xl transition-colors relative" title="Notification Logs">
                <i class="fa-solid fa-envelope text-base"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-emerald-500 rounded-full animate-pulse"></span>
            </button>
            
            <!-- Notification Icon -->
            <button class="{{ $currentTheme['topbar_btn'] }} p-2 rounded-xl transition-colors relative" title="System Alerts">
                <i class="fa-solid fa-bell text-base"></i>
                <span class="absolute top-1 right-1 w-2 h-2 bg-crimson-450 rounded-full animate-ping"></span>
            </button>

            <span class="w-px h-6 bg-white/20"></span>

            <!-- Active User sadmin Dropdown Profile -->
            <div x-data="{ userOpen: false }" class="relative select-none">
                <button @click="userOpen = !userOpen" class="flex items-center gap-2 {{ $currentTheme['topbar_btn'] }} px-3 py-1.5 rounded-xl transition-all focus:outline-none">
                    <div class="w-8 h-8 rounded-lg bg-white/20 border border-white/30 overflow-hidden flex items-center justify-center">
                        <i class="fa-solid fa-user text-sm"></i>
                    </div>
                    <span class="hidden sm:inline text-xs font-bold uppercase tracking-wider">{{ $superAdminUsername }}</span>
                    <i class="fa-solid fa-chevron-down text-[10px] opacity-80 transition-transform" :class="userOpen ? 'rotate-180' : 'rotate-0'"></i>
                </button>
                
                <!-- Dropdown items -->
                <div x-show="userOpen" @click.away="userOpen = false" x-transition class="absolute right-0 mt-2 w-48 bg-white text-slate-800 rounded-2xl shadow-xl border border-slate-100 py-2 text-xs font-semibold" style="display: none;">
                    <a href="{{ route('admin_sys.profile') }}" class="flex items-center gap-2 px-4 py-2.5 hover:bg-slate-50 transition-colors">
                        <i class="fa-solid fa-user-shield {{ $currentTheme['text'] }}"></i> Console Profile
                    </a>
                    <hr class="border-slate-100 my-1">
                    <form action="{{ route('admin_sys.logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full text-left flex items-center gap-2 px-4 py-2.5 hover:bg-rose-50 text-crimson-600 transition-colors">
                            <i class="fa-solid fa-power-off"></i> System Logout
                        </button>
                    </form>
                </div>
            </div>
        </div>

    </header>

    <!-- 2. Main Wrapper Layout with Optional Sidebar -->
    <div class="flex flex-grow w-full">

        <!-- Mobile backdrop overlay drawer dismisser -->
        <div x-show="sidebarOpen" @click="sidebarOpen = false" class="md:hidden fixed inset-0 bg-slate-900/40 backdrop-blur-sm z-45 transition-opacity" style="display: none;"></div>

        <!-- Left Sidebar Navigation Drawer -->
        <aside x-show="sidebarOpen" x-transition:enter="transition ease-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in duration-200 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="fixed inset-y-0 left-0 md:relative w-64 bg-white border-r border-slate-200 flex-shrink-0 flex flex-col justify-between select-none z-50 shadow-2xl md:shadow-none" style="display: none;">
            <div class="py-6 px-4 space-y-6">
                <!-- Sidebar Header Title -->
                <div class="px-2">
                    <span class="text-[9px] font-black text-slate-400 uppercase tracking-widest block mb-1">Super System Console</span>
                    <h3 class="text-xs font-black text-slate-800 uppercase tracking-wider leading-none">Domain Management</h3>
                </div>

                <!-- Navigation links -->
                <nav class="space-y-1.5">
                    <a href="{{ route('admin_sys.company.index') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-bold {{ request()->routeIs('admin_sys.company.index') ? $currentTheme['sidebar_active'] : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800' }} transition-all">
                        <i class="fa-solid fa-network-wired text-sm"></i>
                        <span>Website Overview</span>
                    </a>

                    <a href="{{ route('admin_sys.profile') }}" class="flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold {{ request()->routeIs('admin_sys.profile') ? $currentTheme['sidebar_active'] : 'text-slate-600 hover:bg-slate-50 hover:text-slate-800' }} transition-all">
                        <i class="fa-solid fa-user-shield text-sm"></i>
                        <span>Super Admin Profile</span>
                    </a>
                    <form action="{{ route('admin_sys.logout') }}" method="POST" class="w-full">
                        @csrf
                        <button type="submit" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs font-semibold text-rose-600 hover:bg-rose-50 hover:text-rose-700 transition-all text-left">
                            <i class="fa-solid fa-power-off text-sm"></i>
                            <span>Console Logout</span>
                        </button>
                    </form>
                </nav>
            </div>

            <!-- Footer copyright watermark inside sidebar -->
            <div class="p-4 border-t border-slate-100 text-[9px] text-slate-400 font-semibold uppercase tracking-wider text-center">
                <span>Crackers Multi-Domain v1.0</span>
            </div>
        </aside>

        <!-- Right Main page dynamic content -->
        <main class="flex-grow p-6 md:p-8 overflow-x-hidden">
            @yield('content')
        </main>

    </div>

    <!-- Alert toast display -->
    @if(session('success'))
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            Swal.fire({
                toast: true,
                position: 'top-end',
                icon: 'success',
                title: "{{ session('success') }}",
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true,
            });
        });
    </script>
    @endif

    @if($errors->any())
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            let errorText = {!! json_encode(implode('<br>', $errors->all())) !!};
            Swal.fire({
                icon: 'error',
                title: 'Validation Failed',
                html: '<div class="text-left text-xs font-semibold text-slate-600 leading-relaxed">' + errorText + '</div>',
                confirmButtonColor: '#e51d1d',
                confirmButtonText: 'Understood'
            });
        });
    </script>
    @endif

    @yield('scripts')

</body>
</html>
