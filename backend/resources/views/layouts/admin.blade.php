@php
    $activeTheme = $currentCompany?->theme ?? 'Theme_1';
    
    // Map storefront theme to active layout design style (light topbar 'gold' or colored topbar 'crimson')
    $isLightTheme = in_array(strtolower($activeTheme), ['theme_1']);
    
    if ($isLightTheme) {
        $currentTheme = [
            'active' => 'bg-gold-500 text-slate-950',
            'icon' => 'bg-gold-500 text-slate-950',
            'border' => 'border-gold-500',
            'text' => 'text-gold-500',
            'accent' => 'gold-500',
            'topbar' => 'bg-white border-b border-slate-250 h-16 flex items-center justify-between px-6 select-none shadow-sm',
            'topbar_title' => 'text-slate-500',
            'topbar_badge' => 'text-slate-650 font-bold bg-slate-50 border border-slate-200 text-slate-650'
        ];
    } else {
        $currentTheme = [
            'active' => 'bg-crimson-600 text-white shadow-md shadow-crimson-500/20',
            'icon' => 'bg-gold-500 text-slate-950',
            'border' => 'border-crimson-600',
            'text' => 'text-crimson-600',
            'accent' => 'crimson-600',
            'topbar' => 'bg-crimson-600 border-b border-crimson-700 h-16 flex items-center justify-between px-6 select-none shadow-md text-white',
            'topbar_title' => 'text-white/90',
            'topbar_badge' => 'text-white font-bold bg-crimson-700 border border-crimson-500'
        ];
    }
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>@yield('title', 'Admin Console | ' . App\Models\Setting::get('store_name', 'Cracker Demo') . ' Sivakasi')</title>
    
    <!-- Favicon -->
    <link rel="icon" type="image/png" href="https://img.icons8.com/color/48/settings.png">
    
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
                        @if(strtolower($activeTheme) === 'theme_2')
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        crimson: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_3')
                        gold: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        crimson: {
                            50: '#ecfdf5',
                            100: '#d1fae5',
                            200: '#a7f3d0',
                            300: '#6ee7b7',
                            400: '#34d399',
                            500: '#10b981',
                            600: '#059669',
                            700: '#047857',
                            800: '#065f46',
                            900: '#064e3b',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_4')
                        gold: {
                            50: '#fefce8',
                            100: '#fef9c3',
                            200: '#fef08a',
                            300: '#fde047',
                            400: '#facc15',
                            500: '#eab308',
                            600: '#ca8a04',
                            700: '#a16207',
                            800: '#854d0e',
                            900: '#713f12',
                        },
                        crimson: {
                            50: '#faf5ff',
                            100: '#f3e8ff',
                            200: '#e9d5ff',
                            300: '#d8b4fe',
                            400: '#c084fc',
                            500: '#a855f7',
                            600: '#9333ea',
                            700: '#7e22ce',
                            800: '#6b21a8',
                            900: '#581c87',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_5')
                        gold: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        },
                        crimson: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_6')
                        gold: {
                            50: '#fef2f2',
                            100: '#fee2e2',
                            200: '#fecaca',
                            300: '#fca5a5',
                            400: '#f87171',
                            500: '#ef4444',
                            600: '#dc2626',
                            700: '#b91c1c',
                            800: '#991b1b',
                            900: '#7f1d1d',
                        },
                        crimson: {
                            50: '#ecfeff',
                            100: '#cffafe',
                            200: '#a5f3fc',
                            300: '#67e8f9',
                            400: '#22d3ee',
                            500: '#06b6d4',
                            600: '#0891b2',
                            700: '#0e7490',
                            800: '#155e75',
                            900: '#164e63',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_7')
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        crimson: {
                            50: '#f0fdf4',
                            100: '#dcfce7',
                            200: '#bbf7d0',
                            300: '#86efac',
                            400: '#4ade80',
                            500: '#22c55e',
                            600: '#16a34a',
                            700: '#15803d',
                            800: '#166534',
                            900: '#14532d',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_8')
                        gold: {
                            50: '#fff1f2',
                            100: '#ffe4e6',
                            200: '#fecdd3',
                            300: '#fda4af',
                            400: '#fb7185',
                            500: '#f43f5e',
                            600: '#e11d48',
                            700: '#be123c',
                            800: '#9f1239',
                            900: '#881337',
                        },
                        crimson: {
                            50: '#f0fdfa',
                            100: '#ccfbf1',
                            200: '#99f6e4',
                            300: '#5eead4',
                            400: '#2dd4bf',
                            500: '#14b8a6',
                            600: '#0d9488',
                            700: '#0f766e',
                            800: '#115e59',
                            900: '#134e4a',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_9')
                        gold: {
                            50: '#fffbeb',
                            100: '#fef3c7',
                            200: '#fde68a',
                            300: '#fcd34d',
                            400: '#fbbf24',
                            500: '#f59e0b',
                            600: '#d97706',
                            700: '#b45309',
                            800: '#92400e',
                            900: '#78350f',
                        },
                        crimson: {
                            50: '#f8fafc',
                            100: '#f1f5f9',
                            200: '#e2e8f0',
                            300: '#cbd5e1',
                            400: '#94a3b8',
                            500: '#64748b',
                            600: '#475569',
                            700: '#334155',
                            800: '#1e293b',
                            900: '#0f172a',
                        }
                        @elseif(strtolower($activeTheme) === 'theme_10')
                        gold: {
                            50: '#fff7ed',
                            100: '#ffedd5',
                            200: '#fed7aa',
                            300: '#fdba74',
                            400: '#fb923c',
                            500: '#f97316',
                            600: '#ea580c',
                            700: '#c2410c',
                            800: '#9a3412',
                            900: '#7c2d12',
                        },
                        crimson: {
                            50: '#eef2ff',
                            100: '#e0e7ff',
                            200: '#c7d2fe',
                            300: '#a5b4fc',
                            400: '#818cf8',
                            500: '#6366f1',
                            600: '#4f46e5',
                            700: '#4338ca',
                            800: '#3730a3',
                            900: '#312e81',
                        }
                        @else
                        gold: {
                            50: '#fffdf0',
                            100: '#fef7c3',
                            200: '#fdf196',
                            300: '#fae459',
                            400: '#f8d82d',
                            500: '#e5bf13',
                            600: '#c2960b',
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
                            700: '#c01212',
                            800: '#9f1313',
                            900: '#831616',
                        }
                        @endif
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
    
    <!-- SweetAlert2 for elegant modal notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Alpine.js CDN for Reactive Micro-Interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- Custom styling -->
    <style>
        .custom-scrollbar::-webkit-scrollbar {
            width: 4px;
            height: 4px;
        }
        .custom-scrollbar::-webkit-scrollbar-track {
            background: #f1f5f9;
        }
        .custom-scrollbar::-webkit-scrollbar-thumb {
            background: #cbd5e1;
            border-radius: 9999px;
        }
        /* Compatibility styles for legacy badge classes */
        .bg-danger { background-color: #e11d48 !important; }
        .bg-warning { background-color: #d97706 !important; }
        .bg-success { background-color: #059669 !important; }
        .bg-info { background-color: #0284c7 !important; }
        .bg-primary { background-color: #2563eb !important; }
        .bg-secondary { background-color: #475569 !important; }
        .text-dark { color: #0f172a !important; }
    </style>
</head>
<body x-data="{ mobileSidebarOpen: false }" class="bg-slate-50 text-slate-800 min-h-screen flex font-sans">

    <!-- 1. Mobile Sidebar Navigation Drawer -->
    <div x-show="mobileSidebarOpen" class="fixed inset-0 z-50 flex md:hidden" style="display: none;">
        <!-- Backdrop -->
        <div @click="mobileSidebarOpen = false" x-transition:enter="transition-opacity ease-linear duration-300" x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100" x-transition:leave="transition-opacity ease-linear duration-300" x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0" class="fixed inset-0 bg-slate-900/60 backdrop-blur-sm"></div>

        <!-- Sidebar Content -->
        <div x-transition:enter="transition ease-in-out duration-300 transform" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0" x-transition:leave="transition ease-in-out duration-300 transform" x-transition:leave-start="translate-x-0" x-transition:leave-end="-translate-x-full" class="relative flex-1 flex flex-col max-w-xs w-full bg-slate-900 text-slate-100 shadow-2xl">
            <!-- Close button -->
            <div class="absolute top-0 right-0 -mr-12 pt-4">
                <button @click="mobileSidebarOpen = false" class="ml-1 flex items-center justify-center h-10 w-10 rounded-full focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white text-white hover:text-slate-200">
                    <i class="fa-solid fa-xmark text-lg"></i>
                </button>
            </div>

            <!-- Header Brand -->
            <div class="px-6 py-5 flex items-center gap-3 border-b border-slate-800/80">
                <div class="{{ $currentTheme['icon'] }} p-1.5 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-lock-keyhole text-slate-950 text-base"></i>
                </div>
                <div>
                    <h2 class="text-xs font-black uppercase tracking-widest text-white leading-none">{{ App\Models\Setting::get('store_name', 'Cracker Demo') }}</h2>
                    <span class="text-[9px] text-slate-500 tracking-wider">Management Console</span>
                </div>
            </div>

            <!-- Main Menu Links -->
            <nav class="flex-grow space-y-1.5 px-3 py-6 overflow-y-auto font-semibold">
                <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-chart-line text-sm"></i>
                    <span>Dashboard Metrics</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.orders.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-truck-ramp-box text-sm"></i>
                    <span>Booked Orders</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.products.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-sm"></i>
                    <span>Manage Inventory</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.categories.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-list-check text-sm"></i>
                    <span>Categories List</span>
                </a>
                <a href="{{ route('admin.branding.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.branding.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-globe text-sm"></i>
                    <span>Site Branding</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.settings.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-sliders text-sm"></i>
                    <span>Store Settings</span>
                </a>
                <a href="{{ route('admin.profile.edit') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.profile.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-user-gear text-sm"></i>
                    <span>Admin Profile</span>
                </a>
            </nav>

            <!-- Footer actions inside sidebar -->
            <div class="p-4 border-t border-slate-800/80 space-y-2">
                <a href="/{{ $currentCompany ? '?company=' . $currentCompany->code : '' }}" target="_blank" class="w-full flex items-center justify-center gap-2 py-2 border border-slate-800 hover:border-slate-700 bg-slate-950 hover:bg-slate-900 rounded-xl text-xs font-bold text-slate-300 transition-colors">
                    <i class="fa-solid fa-globe"></i>
                    <span>Open Front Store</span>
                </a>
                <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                    @csrf
                    <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 bg-crimson-800 hover:bg-crimson-700 text-white rounded-xl text-xs font-black transition-colors">
                        <i class="fa-solid fa-arrow-right-from-bracket"></i>
                        <span>Logout</span>
                    </button>
                </form>
            </div>
        </div>
    </div>

    <!-- 2. Desktop Sidebar Navigation (Premium Dark Sidebar) -->
    <aside class="w-64 bg-slate-900 border-r border-slate-800 flex flex-col justify-between flex-shrink-0 select-none hidden md:flex text-slate-100 sticky top-0 h-screen self-start">
        
        <div class="space-y-6 py-6">
            <!-- Header Brand -->
            <div class="px-6 flex items-center gap-3">
                <div class="{{ $currentTheme['icon'] }} p-1.5 rounded-lg flex items-center justify-center">
                    <i class="fa-solid fa-lock-keyhole text-slate-950 text-base"></i>
                </div>
                <div>
                    <h2 class="text-xs font-black uppercase tracking-widest text-white leading-none">{{ App\Models\Setting::get('store_name', 'Cracker Demo') }}</h2>
                    <span class="text-[9px] text-slate-500 tracking-wider">Management Console</span>
                </div>
            </div>

            <!-- Main Menu Links -->
            <nav class="space-y-1.5 px-3 font-semibold">
                <a href="{{ route('admin.dashboard') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.dashboard') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-chart-line text-sm"></i>
                    <span>Dashboard Metrics</span>
                </a>
                <a href="{{ route('admin.orders.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.orders.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-truck-ramp-box text-sm"></i>
                    <span>Booked Orders</span>
                </a>
                <a href="{{ route('admin.products.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.products.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-boxes-stacked text-sm"></i>
                    <span>Manage Inventory</span>
                </a>
                <a href="{{ route('admin.categories.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.categories.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-list-check text-sm"></i>
                    <span>Categories List</span>
                </a>
                <a href="{{ route('admin.branding.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.branding.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-globe text-sm"></i>
                    <span>Site Branding</span>
                </a>
                <a href="{{ route('admin.settings.index') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.settings.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-sliders text-sm"></i>
                    <span>Store Settings</span>
                </a>
                <a href="{{ route('admin.profile.edit') }}" class="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-xs {{ request()->routeIs('admin.profile.*') ? $currentTheme['active'] : 'text-slate-400 hover:bg-slate-800/60 hover:text-slate-200' }} transition-colors">
                    <i class="fa-solid fa-user-gear text-sm"></i>
                    <span>Admin Profile</span>
                </a>
            </nav>
        </div>

        <!-- Footer actions inside sidebar -->
        <div class="p-4 border-t border-slate-800/80 space-y-2">
            <a href="/{{ $currentCompany ? '?company=' . $currentCompany->code : '' }}" target="_blank" class="w-full flex items-center justify-center gap-2 py-2 border border-slate-800 hover:border-slate-700 bg-slate-950 hover:bg-slate-900 rounded-xl text-xs font-bold text-slate-300 transition-colors">
                <i class="fa-solid fa-globe"></i>
                <span>Open Front Store</span>
            </a>
            <form action="{{ route('admin.logout') }}" method="POST" class="w-full">
                @csrf
                <button type="submit" class="w-full flex items-center justify-center gap-2 py-2 bg-crimson-800 hover:bg-crimson-700 text-white rounded-xl text-xs font-black transition-colors">
                    <i class="fa-solid fa-arrow-right-from-bracket"></i>
                    <span>Logout</span>
                </button>
            </form>
        </div>

    </aside>

    <!-- 3. Main content pane (Premium Light Content) -->
    <div class="flex-grow flex flex-col min-w-0">
        
        <!-- Header -->
        <header class="{{ $currentTheme['topbar'] }}">
            <div class="flex items-center gap-4">
                <!-- Mobile Nav toggle -->
                <button @click="mobileSidebarOpen = true" class="md:hidden text-slate-500 hover:text-slate-800 p-2 rounded-lg border border-slate-200 bg-white">
                    <i class="fa-solid fa-bars"></i>
                </button>
                
                <h3 class="text-xs font-bold {{ $currentTheme['topbar_title'] }} uppercase tracking-widest hidden sm:block">{{ App\Models\Setting::get('store_name', 'Cracker Demo') }} Dashboard Control</h3>
            </div>

            <div class="flex items-center gap-3">
                <span class="text-xs {{ $currentTheme['topbar_badge'] }} px-3 py-1 rounded-full flex items-center gap-1.5 shadow-sm">
                    <i class="fa-solid fa-circle text-[8px] text-emerald-500 animate-pulse"></i> Admin Panel
                </span>
            </div>
        </header>

        <!-- Main Workspace container -->
        <main class="flex-grow p-6 overflow-y-auto bg-slate-50 custom-scrollbar">
            
            <!-- SweetAlert flash prompts -->
            @if(session('success'))
            <script>
                Swal.fire({
                    title: 'Success!',
                    text: "{{ session('success') }}",
                    icon: 'success',
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 3000,
                    timerProgressBar: true,
                    confirmButtonColor: '#e5bf13'
                });
            </script>
            @endif

            @yield('content')
        </main>
    </div>

    @yield('scripts')
</body>
</html>
