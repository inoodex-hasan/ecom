<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="h-full dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title') - {{ config('app.name', 'Loomora') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['"Plus Jakarta Sans"', 'sans-serif'],
                    },
                    colors: {
                        brand: {
                            50: '#f0f9ff',
                            100: '#e0f2fe',
                            500: '#0284c7',
                            600: '#0369a1',
                            700: '#075985',
                        }
                    }
                }
            }
        }
    </script>
    <style>
        body {
            font-family: 'Plus Jakarta Sans', sans-serif;
            background: radial-gradient(circle at 50% 0%, #0f172a 0%, #020617 100%);
        }
        .glow-effect {
            position: absolute;
            width: 600px;
            height: 600px;
            background: radial-gradient(circle, rgba(14, 165, 233, 0.12) 0%, rgba(2, 6, 23, 0) 70%);
            top: 10%;
            left: 50%;
            transform: translateX(-50%);
            pointer-events: none;
            z-index: 0;
        }
    </style>
</head>
<body class="h-full text-slate-100 flex flex-col justify-between selection:bg-sky-500 selection:text-white relative overflow-x-hidden">
    <div class="glow-effect"></div>

    <!-- Header Navigation -->
    <header class="w-full max-w-7xl mx-auto px-6 py-6 flex items-center justify-between relative z-10">
        <a href="{{ url('/') }}" class="flex items-center gap-2 group transition-transform hover:scale-105">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-tr from-sky-500 to-indigo-600 flex items-center justify-center shadow-lg shadow-sky-500/20 text-white font-black text-xl tracking-tighter">
                L
            </div>
            <div class="flex flex-col">
                <span class="text-xl font-bold tracking-tight text-white group-hover:text-sky-400 transition-colors">
                    {{ config('app.name', 'Loomora') }}
                </span>
                <span class="text-[10px] tracking-wider uppercase text-slate-400 font-medium">Control Center</span>
            </div>
        </a>

        <div class="flex items-center gap-3">
            @auth
                <a href="{{ route('admin.dashboard') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3.5 py-2 rounded-lg bg-slate-800/60 border border-slate-700/60 transition-all hover:bg-slate-800">
                    Admin Dashboard
                </a>
            @else
                <a href="{{ route('login') }}" class="text-sm font-medium text-slate-300 hover:text-white px-3.5 py-2 rounded-lg bg-slate-800/60 border border-slate-700/60 transition-all hover:bg-slate-800">
                    Sign In
                </a>
            @endauth
        </div>
    </header>

    <!-- Main Content -->
    <main class="w-full max-w-3xl mx-auto px-6 py-12 text-center relative z-10 flex-1 flex flex-col items-center justify-center">
        @yield('content')
    </main>

    <!-- Footer -->
    <footer class="w-full max-w-7xl mx-auto px-6 py-6 text-center text-xs text-slate-500 relative z-10">
        <p>&copy; {{ date('Y') }} {{ config('app.name', 'Loomora') }} Lifestyle Ltd. All rights reserved.</p>
    </footer>
</body>
</html>
