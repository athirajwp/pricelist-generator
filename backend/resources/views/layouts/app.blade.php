@php
    $activeTheme = $currentCompany?->theme ?? 'Theme_1';
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($currentCompany?->name ?? App\Models\Setting::get('store_name', 'Cracker Demo')) . ' | Sivakasi Online Crackers Shop')</title>
    
    <!-- Favicon -->
    @if($currentCompany && $currentCompany->favicon_path)
        <link rel="icon" type="image/png" href="/{{ $currentCompany->favicon_path }}">
    @else
        <link rel="icon" type="image/png" href="https://img.icons8.com/color/48/fireworks.png">
    @endif
    
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
    
    <!-- Alpine.js CDN for Reactive Micro-Interactions -->
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>
    
    <!-- SweetAlert2 for elegant modal notifications -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    
    <!-- Custom animations and styles -->
    <style>
        html {
            scroll-behavior: smooth;
        }
        
        .glassmorphism {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1px solid rgba(0, 0, 0, 0.08);
        }
        
        .glow-gold:hover {
            box-shadow: 0 0 15px rgba(229, 191, 19, 0.4);
        }

        .marquee-container {
            overflow: hidden;
            white-space: nowrap;
        }

        .marquee-content {
            display: inline-block;
            animation: marquee 25s linear infinite;
        }

        @keyframes marquee {
            0% { transform: translateX(100%); }
            100% { transform: translateX(-100%); }
        }
        
        .map-container iframe {
            width: 100% !important;
            height: 100% !important;
            border: 0;
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
<body class="bg-slate-50 text-slate-800 min-h-screen flex flex-col font-sans selection:bg-gold-500 selection:text-slate-900">
    
    <!-- 1. Scrolling Marquee Header Alert -->
    @php
        $marqueeAlerts = [
            1 => ['icon' => 'fa-bullhorn', 'color' => 'text-gold-200', 'default' => 'Fresh and Warm Bakes Everyday'],
            2 => ['icon' => 'fa-circle-exclamation', 'color' => '', 'default' => 'Minimum Order Value for Sivakasi Delivery is <strong>₹1000</strong>'],
            3 => ['icon' => 'fa-fire', 'color' => '', 'default' => 'Celebrate Diwali / Festivals with Flat <strong>60% Discount</strong>!'],
            4 => ['icon' => 'fa-truck-fast', 'color' => '', 'default' => 'Express Lorry Transport Delivery Across Kerala, Karnataka, Tamilnadu, Andhra & Telangana!'],
            5 => ['icon' => 'fa-phone', 'color' => '', 'default' => 'For Enquiries, Contact Support: <strong>8682942042</strong>'],
            6 => ['icon' => 'fa-shield-halved', 'color' => '', 'default' => '100% Quality & Safe Sivakasi Manufactured Crackers']
        ];
    @endphp
    <div class="bg-crimson-700 border-b border-crimson-800 text-white py-2 text-xs font-semibold marquee-container shadow-sm select-none">
        <div class="marquee-content flex gap-12 items-center">
            @foreach($marqueeAlerts as $num => $alert)
                @php
                    $val = App\Models\Setting::get("marquee_alert_{$num}", $alert['default']);
                @endphp
                @if(!empty(trim($val)))
                    <span class="{{ $alert['color'] }}">
                        <i class="fa-solid {{ $alert['icon'] }} text-gold-300 mr-2"></i>
                        {!! $val !!}
                    </span>
                @endif
            @endforeach
        </div>
    </div>

    <!-- 2. Main Premium Glass Navbar -->
    <header x-data="{ mobileMenuOpen: false }" class="sticky top-0 z-50 bg-white/90 backdrop-blur-md border-b border-slate-200/80 shadow-sm">
        <div class="container mx-auto px-4 py-3 flex justify-between items-center">
            
            <!-- Logo / Branding -->
            <a href="/" class="flex items-center gap-2 md:gap-3 group flex-shrink-0">
                @if($currentCompany && $currentCompany->logo_path)
                    <img src="/{{ $currentCompany->logo_path }}" alt="Logo" class="h-8 md:h-10 object-contain max-w-[160px]">
                @else
                    <div class="bg-gradient-to-tr from-gold-500 to-crimson-600 p-1.5 md:p-2 rounded-xl shadow-md group-hover:scale-105 transition-transform duration-300">
                        <i class="{{ $currentCompany?->logo_icon ?? 'fa-solid fa-fire-burner' }} text-lg md:text-2xl text-white"></i>
                    </div>
                @endif
                <div class="flex flex-col justify-center">
                    <h1 class="text-xs sm:text-sm md:text-base lg:text-lg font-black tracking-tight bg-gradient-to-r from-crimson-600 to-gold-500 bg-clip-text text-transparent group-hover:opacity-95 transition-opacity leading-none">
                        {{ strtoupper($currentCompany?->name ?? App\Models\Setting::get('store_name', 'Cracker Demo')) }}
                    </h1>
                    <p class="text-[8px] md:text-[9px] text-slate-500 tracking-widest uppercase font-semibold leading-none mt-1">
                        {{ $currentCompany?->tagline ?? 'Sivakasi Online Booking' }}
                    </p>
                </div>
            </a>

            <!-- Nav Links -->
            <nav class="hidden md:flex items-center gap-6 text-sm font-semibold text-slate-650">
                <a href="/" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-house mr-1.5 text-xs text-crimson-500"></i>Home</a>
                <a href="/#quick-order" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-basket-shopping mr-1.5 text-xs text-crimson-500"></i>Quick Order</a>
                <a href="{{ route('price_list') }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-list-check mr-1.5 text-xs text-crimson-500"></i>Price List</a>
                <a href="/track" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-magnifying-glass mr-1.5 text-xs text-crimson-500"></i>Track Order</a>
                <a href="{{ route('about') }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-circle-info mr-1.5 text-xs text-crimson-500"></i>About Us</a>
            </nav>

            <!-- CTA / Contact Actions -->
            <div class="flex items-center gap-2 md:gap-3 flex-shrink-0">
                <a href="tel:{{ App\Models\Setting::get('store_phone', '+919998887776') }}" class="hidden lg:flex items-center gap-2 bg-slate-100 border border-slate-200 hover:border-slate-300 px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-700 hover:bg-slate-200 transition-all shadow-sm">
                    <i class="fa-solid fa-phone text-crimson-600"></i>
                    <span>{{ App\Models\Setting::get('store_phone', '+91 9998887776') }}</span>
                </a>
                
                <!-- Desktop WhatsApp Button -->
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', App\Models\Setting::get('store_whatsapp', '919998887776')) }}" target="_blank" class="hidden sm:flex bg-emerald-600 hover:bg-emerald-500 text-white px-4 py-1.5 rounded-full text-xs font-extrabold items-center gap-1.5 shadow-sm hover:scale-105 transition-all">
                    <i class="fa-brands fa-whatsapp text-sm"></i>
                    <span>WhatsApp Booking</span>
                </a>
                
                <!-- Mobile Compact WhatsApp Button (Icon only) -->
                <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', App\Models\Setting::get('store_whatsapp', '919998887776')) }}" target="_blank" class="sm:hidden flex bg-emerald-600 hover:bg-emerald-500 text-white w-8 h-8 rounded-xl items-center justify-center shadow-sm hover:scale-105 transition-all" title="WhatsApp Booking">
                    <i class="fa-brands fa-whatsapp text-base"></i>
                </a>
                
                <!-- Admin login shortcut (Hidden on mobile header, available in mobile dropdown) -->
                <a href="{{ route('admin.login', ['company' => $currentCompany?->code]) }}" class="hidden sm:flex items-center gap-2 bg-slate-100 border border-slate-200 hover:border-slate-300 px-3.5 py-1.5 rounded-full text-xs font-bold text-slate-700 hover:bg-slate-200 transition-all shadow-sm" title="Admin Portal">
                    <i class="fa-solid fa-lock text-crimson-600"></i>
                    <span>Admin Portal</span>
                </a>

                <!-- Mobile Menu Hamburger Toggler -->
                <button @click="mobileMenuOpen = !mobileMenuOpen" class="md:hidden text-slate-500 hover:text-slate-800 p-2 rounded-xl border border-slate-200 focus:outline-none transition-colors" title="Toggle Navigation Menu">
                    <i :class="mobileMenuOpen ? 'fa-solid fa-xmark text-sm' : 'fa-solid fa-bars text-sm'"></i>
                </button>
            </div>
        </div>

        <!-- Mobile Dropdown Navigation Menu -->
        <div x-show="mobileMenuOpen" 
             x-transition:enter="transition ease-out duration-200" 
             x-transition:enter-start="opacity-0 -translate-y-4" 
             x-transition:enter-end="opacity-100 translate-y-0" 
             x-transition:leave="transition ease-in duration-150" 
             x-transition:leave-start="opacity-100 translate-y-0" 
             x-transition:leave-end="opacity-0 -translate-y-4" 
             class="md:hidden bg-white border-t border-slate-200/80 px-4 py-4 space-y-3 shadow-md"
             style="display: none;">
            <a href="/#quick-order" class="block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-650 hover:bg-slate-50 hover:text-crimson-600 transition-all flex items-center gap-2">
                <i class="fa-solid fa-house text-crimson-500 text-[10px]"></i> Home / Quick Order
            </a>
            <a href="{{ route('price_list') }}" class="block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-650 hover:bg-slate-50 hover:text-crimson-600 transition-all flex items-center gap-2">
                <i class="fa-solid fa-list-check text-crimson-500 text-[10px]"></i> Wholesale Price List
            </a>
            <a href="/track" class="block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-650 hover:bg-slate-50 hover:text-crimson-600 transition-all flex items-center gap-2">
                <i class="fa-solid fa-magnifying-glass text-crimson-500 text-[10px]"></i> Track Your Order
            </a>
            <a href="{{ route('about') }}" @click="mobileMenuOpen = false" class="block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-650 hover:bg-slate-50 hover:text-crimson-600 transition-all flex items-center gap-2">
                <i class="fa-solid fa-circle-info text-crimson-500 text-[10px]"></i> About Us / Contact
            </a>
            <a href="{{ route('admin.login', ['company' => $currentCompany?->code]) }}" class="block px-4 py-2.5 rounded-xl text-xs font-bold text-slate-650 hover:bg-slate-50 hover:text-crimson-600 transition-all flex items-center gap-2">
                <i class="fa-solid fa-lock text-crimson-500 text-[10px]"></i> Admin Console Gate
            </a>
            <div class="border-t border-slate-100 pt-3 flex flex-col gap-2">
                <a href="tel:{{ App\Models\Setting::get('store_phone', '+919998887776') }}" class="w-full flex items-center justify-center gap-2 bg-slate-100 border border-slate-200 px-3.5 py-2.5 rounded-full text-xs font-bold text-slate-700 hover:bg-slate-200 transition-all shadow-sm">
                    <i class="fa-solid fa-phone text-crimson-600"></i>
                    <span>Call Support: {{ App\Models\Setting::get('store_phone', '+91 9998887776') }}</span>
                </a>
            </div>
        </div>
    </header>

    <!-- 3. Dynamic Page Content -->
    <main class="flex-grow">
        @yield('content')
    </main>

    <!-- 4. Premium Responsive Footer -->
    <footer class="bg-slate-100 border-t border-slate-200 py-12 relative overflow-hidden" id="about-us">
        <div class="container mx-auto px-4 relative z-10">
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
                
                <!-- Corporate Info -->
                <div class="space-y-4">
                    <div class="flex items-center gap-2">
                        <div class="bg-gold-500 p-1.5 rounded-lg">
                            <i class="fa-solid fa-fire-burner text-slate-900"></i>
                        </div>
                        <span class="font-extrabold text-lg text-slate-800">{{ strtoupper(App\Models\Setting::get('store_name', 'Cracker Demo')) }}</span>
                    </div>
                    <p class="text-xs text-slate-550 leading-relaxed">
                        {{ App\Models\Setting::get('store_name', 'Cracker Demo') }} is a premier firecrackers retailer based in Sivakasi, Virudhunagar, Sivakasi Main Road. We deliver pure joy, colors, and dazzling displays across India, observing the highest safety codes.
                    </p>
                    <div class="flex gap-4 text-slate-400 text-base">
                        @if($fb = App\Models\Setting::get('facebook_link'))
                            <a href="{{ $fb }}" target="_blank" class="hover:text-crimson-600 transition-all hover:scale-110" title="Facebook"><i class="fa-brands fa-facebook-f"></i></a>
                        @endif
                        @if($ig = App\Models\Setting::get('instagram_link'))
                            <a href="{{ $ig }}" target="_blank" class="hover:text-crimson-600 transition-all hover:scale-110" title="Instagram"><i class="fa-brands fa-instagram"></i></a>
                        @endif
                        @if($yt = App\Models\Setting::get('youtube_link'))
                            <a href="{{ $yt }}" target="_blank" class="hover:text-crimson-600 transition-all hover:scale-110" title="YouTube"><i class="fa-brands fa-youtube"></i></a>
                        @endif
                        @if($tw = App\Models\Setting::get('twitter_link'))
                            <a href="{{ $tw }}" target="_blank" class="hover:text-crimson-600 transition-all hover:scale-110" title="Twitter"><i class="fa-brands fa-x-twitter fa-twitter"></i></a>
                        @endif
                        @php
                            $waLink = App\Models\Setting::get('whatsapp_link') ?: 'https://wa.me/' . preg_replace('/[^0-9]/', '', App\Models\Setting::get('store_whatsapp', '919998887776'));
                        @endphp
                        <a href="{{ $waLink }}" target="_blank" class="hover:text-crimson-600 transition-all hover:scale-110" title="WhatsApp"><i class="fa-brands fa-whatsapp"></i></a>
                    </div>
                </div>

                <!-- Fast Links -->
                <div>
                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">Quick Navigation</h4>
                    <ul class="space-y-2 text-xs text-slate-500 font-semibold">
                        <li><a href="/#quick-order" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>Home / Quick Order</a></li>
                        <li><a href="{{ route('price_list') }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>Wholesale Price List</a></li>
                        <li><a href="{{ route('about') }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>About Us</a></li>
                        <li><a href="{{ route('terms') }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>Terms & Conditions</a></li>
                        <li><a href="/track" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>Track Order</a></li>
                        <li><a href="{{ route('admin.login', ['company' => $currentCompany?->code]) }}" class="hover:text-crimson-600 transition-colors"><i class="fa-solid fa-chevron-right mr-1.5 text-[8px] text-crimson-500"></i>Admin Portal</a></li>
                    </ul>
                </div>

                <!-- Contact Particulars -->
                <div>
                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">Contact Details</h4>
                    <ul class="space-y-3 text-xs text-slate-500 mb-4">
                        <li class="flex items-start gap-2.5">
                            <i class="fa-solid fa-location-dot text-crimson-500 mt-0.5"></i>
                            <span>{{ App\Models\Setting::get('store_address', 'Virudhunagar to Sivakasi Main Road, Sivakasi') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-phone text-crimson-500"></i>
                            <span>{{ App\Models\Setting::get('store_phone', '+91 9998887776') }}</span>
                        </li>
                        <li class="flex items-center gap-2.5">
                            <i class="fa-solid fa-envelope text-crimson-500"></i>
                            <span>{{ App\Models\Setting::get('store_email', 'crackerdemo@gmail.com') }}</span>
                        </li>
                    </ul>
                    <!-- Compact Google Map Iframe -->
                    <div class="map-container w-full h-24 rounded-xl overflow-hidden border border-slate-200 shadow-sm opacity-80 hover:opacity-100 transition-opacity">
                        @if($mapIframe = App\Models\Setting::get('store_map_iframe'))
                            {!! $mapIframe !!}
                        @else
                            <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31484.78768782782!2d77.78440079999999!3d9.4475475!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x3b06cee41fe51a8d%3A0xe964a2754897f1f!2sSivakasi%2C%20Tamil%20Nadu!5e0!3m2!1sen!2sin!4v1717830000000!5m2!1sen!2sin" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                        @endif
                    </div>
                </div>

                <!-- Booking Safety Reminder -->
                <div>
                    <h4 class="text-sm font-bold text-slate-700 uppercase tracking-widest border-b border-slate-200 pb-2 mb-4">Safety Disclaimer</h4>
                    <div class="bg-white border border-slate-200 p-3 rounded-lg text-[10px] text-slate-500 leading-normal space-y-1.5">
                        <p class="text-crimson-600 font-bold"><i class="fa-solid fa-triangle-exclamation mr-1"></i>Burst Wisely & Safely:</p>
                        <p>1. Keep a water bucket & fire extinguisher handy when bursting crackers.</p>
                        <p>2. Children must always perform fireworks under strict adult supervision.</p>
                        <p>3. Do not wear loose synthetic clothes near crackers; prefer thick cotton.</p>
                    </div>
                </div>

            </div>

            <!-- Court Order & Legal Compliance Disclaimer -->
            <div class="mt-10 border-t border-slate-200 pt-8 select-none">
                <div class="bg-amber-50/50 border border-amber-200/80 rounded-2xl p-5 md:p-6 text-[10px] sm:text-[11px] text-slate-550 leading-relaxed shadow-sm">
                    <p class="font-extrabold text-amber-800 flex items-center gap-2 text-xs mb-2">
                        <i class="fa-solid fa-gavel text-amber-700 text-sm"></i>
                        <span>Supreme Court Order & Legal Compliance Notice</span>
                    </p>
                    <p class="leading-relaxed">
                        As per 2018 Supreme Court Order, Online Sale of Firecrackers are NOT permitted. We Value our customers and at the same time, we respect the jurisdiction. We request our customers to Select Your Products in Estimate Page to see your Estimation and Submit the required crackers through the Get Estimate Button. We will contact you within 2 hrs and Confirm the Order through Phone Call. Please Add and Submit Your enquiries and enjoy your Diwali with jallikattu crackers. Jallikattu Crackers Shop is a shop following 100% legal & statutory compliances and all our shops, go-downs are maintained as per the explosive acts. Our License Name: <strong class="font-extrabold text-slate-800">{{ App\Models\Setting::get('license_name', '**') }}</strong> Licence No is <strong class="font-extrabold text-slate-800">{{ App\Models\Setting::get('license_no', '***') }}</strong>. We send the parcels through registered and legal transport service providers as like every other major Companies in Sivakasi is doing so.
                    </p>
                </div>
            </div>

            <!-- Bottom Credits -->
            <div class="border-t border-slate-200 mt-8 pt-6 flex flex-col md:flex-row justify-between items-center text-[10px] text-slate-400 gap-4">
                <p>&copy; 2026 {{ App\Models\Setting::get('store_name', 'Cracker Demo') }} Sivakasi. All Rights Reserved. Designed by pairs.</p>
                <div class="flex gap-4 font-semibold">
                    <span class="hover:text-slate-655 cursor-pointer">Privacy Policy</span>
                    <span>&bull;</span>
                    <a href="{{ route('terms') }}" class="hover:text-slate-655 transition-colors">Terms of Booking</a>
                </div>
            </div>
        </div>
    </footer>

    <!-- 5. Floating Quick Action Dot (Back to Top Navigation Dot) -->
    <div x-data="{ showScrollTop: false }" 
         x-init="window.addEventListener('scroll', () => { showScrollTop = window.scrollY > 300 })"
         x-show="showScrollTop"
         x-transition:enter="transition ease-out duration-300"
         x-transition:enter-start="translate-y-16 opacity-0"
         x-transition:enter-end="translate-y-0 opacity-100"
         x-transition:leave="transition ease-in duration-200"
         x-transition:leave-start="translate-y-0 opacity-100"
         x-transition:leave-end="translate-y-16 opacity-0"
         class="fixed bottom-6 right-6 z-45 select-none pointer-events-auto"
         style="display: none;">
        <button @click="window.scrollTo({ top: 0, behavior: 'smooth' })" 
                class="w-12 h-12 bg-gradient-to-tr from-gold-500 to-amber-500 text-slate-950 rounded-full flex items-center justify-center shadow-lg hover:scale-110 active:scale-95 transition-all duration-300 border border-gold-400/30 group" 
                title="Scroll to Top">
            <i class="fa-solid fa-arrow-up text-sm group-hover:-translate-y-0.5 transition-transform"></i>
        </button>
    </div>

    @yield('scripts')
</body>
</html>
