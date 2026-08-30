@php
    $theme = App\Models\Setting::get('admin_theme', 'gold');
    $storeName = App\Models\Setting::get('store_name', 'Cracker Demo');
    
    // Theme colors mapping
    $themeColors = [
        'gold' => [
            'primary' => '#e5bf13',
            'primary_dark' => '#c2960b',
            'primary_darker' => '#9b7009',
            'bg_50' => '#fffdf0',
            'glass_border' => 'rgba(229, 191, 19, 0.25)',
            'glow' => 'rgba(229, 191, 19, 0.1)',
            'btn_text' => 'text-slate-950',
        ],
        'blue' => [
            'primary' => '#2563eb',
            'primary_dark' => '#1d4ed8',
            'primary_darker' => '#1e40af',
            'bg_50' => '#eff6ff',
            'glass_border' => 'rgba(37, 99, 235, 0.25)',
            'glow' => 'rgba(37, 99, 235, 0.08)',
            'btn_text' => 'text-white',
        ],
        'crimson' => [
            'primary' => '#e11d48',
            'primary_dark' => '#be123c',
            'primary_darker' => '#9f1239',
            'bg_50' => '#fff1f2',
            'glass_border' => 'rgba(225, 29, 72, 0.25)',
            'glow' => 'rgba(225, 29, 72, 0.08)',
            'btn_text' => 'text-white',
        ],
        'emerald' => [
            'primary' => '#059669',
            'primary_dark' => '#047857',
            'primary_darker' => '#065f46',
            'bg_50' => '#ecfdf5',
            'glass_border' => 'rgba(5, 150, 105, 0.25)',
            'glow' => 'rgba(5, 150, 105, 0.08)',
            'btn_text' => 'text-white',
        ]
    ];
    
    $activeTheme = $themeColors[$theme] ?? $themeColors['gold'];
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | {{ $storeName }}</title>
    
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
                        active: {
                            50: '{{ $activeTheme['bg_50'] }}',
                            500: '{{ $activeTheme['primary'] }}',
                            600: '{{ $activeTheme['primary_dark'] }}',
                            700: '{{ $activeTheme['primary_darker'] }}',
                        },
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
                    }
                }
            }
        }
    </script>
    
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        .glass-box {
            background: rgba(255, 255, 255, 0.75);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid {{ $activeTheme['glass_border'] }};
            box-shadow: 0 20px 40px -15px rgba(0, 0, 0, 0.08);
        }
        
        .sparkle-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            background-color: {{ $activeTheme['primary'] }};
            border-radius: 50%;
            filter: drop-shadow(0 0 4px {{ $activeTheme['primary'] }});
            animation: float-sparkle 6s infinite ease-in-out;
        }

        @keyframes float-sparkle {
            0%, 100% { transform: translateY(0px) scale(0.8); opacity: 0.3; }
            50% { transform: translateY(-20px) scale(1.2); opacity: 0.9; }
        }
    </style>
</head>
<body class="bg-slate-50 text-slate-800 min-h-screen flex items-center justify-center font-sans relative overflow-hidden select-none">
    
    <!-- Decorative Floating firework details / glows -->
    <div class="absolute w-[600px] h-[600px] bg-[radial-gradient(circle,_{{ $activeTheme['glow'] }}_0%,_rgba(0,0,0,0)_70%)] -top-44 -left-44 pointer-events-none"></div>
    <div class="absolute w-[600px] h-[600px] bg-[radial-gradient(circle,_rgba(248,59,59,0.06)_0%,_rgba(0,0,0,0)_70%)] -bottom-44 -right-44 pointer-events-none"></div>
    
    <!-- Animated Sparkles in background -->
    <div class="sparkle-dot top-1/4 left-1/5" style="animation-delay: 0s;"></div>
    <div class="sparkle-dot top-1/3 right-1/4" style="animation-delay: 1.5s;"></div>
    <div class="sparkle-dot bottom-1/4 left-1/3" style="animation-delay: 3s;"></div>
    <div class="sparkle-dot bottom-1/3 right-1/5" style="animation-delay: 4.5s;"></div>
 
    <!-- Login card wrapper -->
    <div class="w-full max-w-sm px-4 relative z-10">
        
        <div class="glass-box p-8 rounded-3xl space-y-6">
            
            <!-- Branding Header -->
            <div class="text-center space-y-3">
                <a href="/{{ $currentCompany ? '?company=' . $currentCompany->code : '' }}" class="inline-flex items-center justify-center p-4 bg-gradient-to-tr from-active-500 to-active-600 rounded-2xl shadow-md hover:scale-105 transition-transform duration-300 {{ $activeTheme['btn_text'] }}">
                    <i class="fa-solid fa-lock-keyhole text-2xl"></i>
                </a>
                <div>
                    <h2 class="text-lg font-black tracking-wider uppercase text-slate-850 pt-1 leading-tight">{{ $storeName }}</h2>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest leading-none font-semibold mt-1">Admin Dashboard Entry</p>
                </div>
            </div>

            <!-- Validation Error Alert -->
            @if($errors->has('password'))
            <div class="bg-crimson-50 border border-crimson-200 text-crimson-750 p-3 rounded-xl text-[10px] font-bold flex items-start gap-2 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mt-0.5 text-xs text-crimson-600"></i>
                <span>{{ $errors->first('password') }}</span>
            </div>
            @endif

            <!-- Auth Form -->
            <form action="{{ route('admin.login.post', ['company' => $currentCompany?->code]) }}" method="POST" class="space-y-4">
                @csrf
                
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider mb-1 px-1">Console Master Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-slate-400">
                            <i class="fa-solid fa-key-skeleton text-xs"></i>
                        </span>
                        <input type="password" name="password" id="password-input" required placeholder="••••••••" class="w-full bg-slate-50 border border-slate-200 focus:border-active-500 focus:bg-white rounded-xl py-3.5 pl-9 pr-10 text-xs text-slate-800 focus:ring-1 focus:ring-active-500 focus:outline-none transition-all placeholder-slate-400">
                        <button type="button" onclick="const input = document.getElementById('password-input'); if (input.type === 'password') { input.type = 'text'; this.firstElementChild.className = 'fa-solid fa-eye-slash text-xs'; } else { input.type = 'password'; this.firstElementChild.className = 'fa-solid fa-eye text-xs'; }" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-active-600 transition-colors focus:outline-none" title="Toggle password visibility">
                            <i class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-active-500 to-active-600 hover:from-active-600 hover:to-active-700 {{ $activeTheme['btn_text'] }} font-extrabold py-3.5 rounded-xl text-xs uppercase tracking-wider shadow-md transform active:scale-95 transition-all flex items-center justify-center gap-1.5 pt-4">
                    <i class="fa-solid fa-right-to-bracket text-[11px]"></i>
                    <span>Authenticate Entry</span>
                </button>
            </form>

            <div class="text-center">
                <a href="/{{ $currentCompany ? '?company=' . $currentCompany->code : '' }}" class="text-[10px] font-bold text-slate-500 hover:text-active-600 transition-colors uppercase tracking-widest"><i class="fa-solid fa-arrow-left mr-1"></i> Back to store</a>
            </div>

        </div>

    </div>

</body>
</html>
