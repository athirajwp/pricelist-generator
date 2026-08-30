<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Login | System Console</title>
    
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
            background: rgba(255, 255, 255, 0.82);
            backdrop-filter: blur(20px);
            -webkit-backdrop-filter: blur(20px);
            border: 1px solid rgba(229, 191, 19, 0.3);
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.12);
        }
        
        .sparkle-dot {
            position: absolute;
            width: 6px;
            height: 6px;
            background-color: #e5bf13;
            border-radius: 50%;
            filter: drop-shadow(0 0 5px #e5bf13);
            animation: float-sparkle 6s infinite ease-in-out;
        }

        @keyframes float-sparkle {
            0%, 100% { transform: translateY(0px) scale(0.8); opacity: 0.3; }
            50% { transform: translateY(-25px) scale(1.3); opacity: 0.9; }
        }
    </style>
</head>
<body class="bg-slate-900 text-slate-100 min-h-screen flex items-center justify-center font-sans relative overflow-hidden select-none">
    
    <!-- Background glows -->
    <div class="absolute w-[800px] h-[800px] bg-[radial-gradient(circle,_rgba(229,191,19,0.06)_0%,_rgba(0,0,0,0)_70%)] -top-96 -left-96 pointer-events-none"></div>
    <div class="absolute w-[800px] h-[800px] bg-[radial-gradient(circle,_rgba(248,59,59,0.04)_0%,_rgba(0,0,0,0)_70%)] -bottom-96 -right-96 pointer-events-none"></div>
    
    <!-- Sparkles -->
    <div class="sparkle-dot top-1/4 left-1/4" style="animation-delay: 0s;"></div>
    <div class="sparkle-dot top-1/3 right-1/3" style="animation-delay: 2s;"></div>
    <div class="sparkle-dot bottom-1/4 left-1/3" style="animation-delay: 4s;"></div>
    
    <!-- Content wrapper -->
    <div class="w-full max-w-md px-6 relative z-10">
        
        <div class="glass-box p-10 rounded-3xl space-y-7 text-slate-800">
            
            <!-- Header -->
            <div class="text-center space-y-3 select-none">
                <div class="inline-flex items-center justify-center p-4 bg-gradient-to-tr from-gold-500 to-amber-600 rounded-2xl shadow-md transform hover:rotate-12 transition-transform duration-300">
                    <i class="fa-solid fa-server text-2xl text-slate-950"></i>
                </div>
                <div>
                    <h2 class="text-lg font-black tracking-wider uppercase text-slate-900">System Console</h2>
                    <p class="text-[10px] text-slate-500 uppercase tracking-widest font-extrabold mt-1">Super Admin Authentication</p>
                </div>
            </div>

            <!-- Error box -->
            @if($errors->has('auth_failed'))
            <div class="bg-crimson-50 border border-crimson-200 text-crimson-750 p-3.5 rounded-xl text-xs font-semibold flex items-start gap-2.5 shadow-sm">
                <i class="fa-solid fa-circle-exclamation mt-0.5 text-sm text-crimson-600"></i>
                <span>{{ $errors->first('auth_failed') }}</span>
            </div>
            @endif

            <!-- Form -->
            <form action="{{ route('admin_sys.login.post') }}" method="POST" class="space-y-5">
                @csrf
                
                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-1">Username</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-user-shield text-xs"></i>
                        </span>
                        <input type="text" name="username" value="{{ old('username') }}" required placeholder="Username" class="w-full bg-white/70 border border-slate-250 focus:border-gold-500 focus:bg-white rounded-xl py-3.5 pl-10 pr-4 text-xs text-slate-800 focus:ring-1 focus:ring-gold-500 focus:outline-none transition-all placeholder-slate-400 font-semibold shadow-inner">
                    </div>
                </div>

                <div class="space-y-1.5">
                    <label class="block text-[9px] font-bold text-slate-500 uppercase tracking-wider px-1">Password</label>
                    <div class="relative">
                        <span class="absolute inset-y-0 left-0 flex items-center pl-3.5 text-slate-400">
                            <i class="fa-solid fa-lock text-xs"></i>
                        </span>
                        <input type="password" name="password" required placeholder="••••••••" class="w-full bg-white/70 border border-slate-250 focus:border-gold-500 focus:bg-white rounded-xl py-3.5 pl-10 pr-10 text-xs text-slate-800 focus:ring-1 focus:ring-gold-500 focus:outline-none transition-all placeholder-slate-400 font-mono shadow-inner">
                        <button type="button" onclick="const input = this.previousElementSibling; if (input.type === 'password') { input.type = 'text'; this.firstElementChild.className = 'fa-solid fa-eye-slash text-xs'; } else { input.type = 'password'; this.firstElementChild.className = 'fa-solid fa-eye text-xs'; }" class="absolute inset-y-0 right-0 flex items-center pr-3.5 text-slate-400 hover:text-gold-600 transition-colors focus:outline-none" title="Toggle password visibility">
                            <i class="fa-solid fa-eye text-xs"></i>
                        </button>
                    </div>
                </div>

                <button type="submit" class="w-full bg-gradient-to-r from-gold-500 to-amber-500 hover:from-gold-600 hover:to-amber-600 text-slate-950 font-black py-4 rounded-xl text-xs uppercase tracking-wider shadow-md transform active:scale-95 transition-all flex items-center justify-center gap-2 mt-2">
                    <i class="fa-solid fa-right-to-bracket text-sm"></i>
                    <span>Login to Console</span>
                </button>
            </form>

            <div class="text-center">
                <a href="/" class="text-[10px] font-bold text-slate-500 hover:text-crimson-600 transition-colors uppercase tracking-widest"><i class="fa-solid fa-house mr-1"></i> Back to store</a>
            </div>

        </div>
        
    </div>

</body>
</html>
