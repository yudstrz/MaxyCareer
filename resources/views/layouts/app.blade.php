<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" class="dark">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'MaxyCareer - AI CV ATS Analyst')</title>

    <!-- Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700&display=swap" rel="stylesheet">

    <!-- Scripts and Styles -->
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="antialiased min-h-screen">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-screen w-72 glass-panel border-r border-white/5 z-50 overflow-y-auto">
            <div class="p-8">
                <div class="flex items-center gap-3 mb-10">
                    <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-primary-500 to-accent-500 flex items-center justify-center shadow-lg shadow-primary-500/20">
                        <span class="text-white font-bold text-xl">M</span>
                    </div>
                    <div>
                        <h1 class="text-xl font-bold tracking-tight">Maxy<span class="text-primary-400">Career</span></h1>
                        <p class="text-[10px] text-white/40 uppercase tracking-[0.2em] font-medium">AI Career Engine</p>
                    </div>
                </div>

                <nav class="space-y-2">
                    <a href="/" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->is('/') ? 'bg-primary-500/10 border border-primary-500/20 text-primary-400 font-medium' : 'hover:bg-white/5 border border-transparent hover:border-white/10 text-white/50 hover:text-white/80' }} transition-all group">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>CV ATS Analyst</span>
                        @if(request()->is('/'))<div class="ml-auto w-1.5 h-1.5 rounded-full bg-primary-400 shadow-[0_0_8px_rgba(96,165,250,0.8)]"></div>@endif
                    </a>

                    <a href="{{ route('job-matching') }}" class="flex items-center gap-3 px-4 py-3 rounded-xl {{ request()->routeIs('job-matching') ? 'bg-primary-500/10 border border-primary-500/20 text-primary-400 font-medium' : 'hover:bg-white/5 border border-transparent hover:border-white/10 text-white/50 hover:text-white/80' }} transition-all group">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>Job Matching</span>
                        @if(request()->routeIs('job-matching'))<div class="ml-auto w-1.5 h-1.5 rounded-full bg-primary-400 shadow-[0_0_8px_rgba(96,165,250,0.8)]"></div>@endif
                    </a>

                    @php
                        $menus = [
                            ['name' => 'Psychological Test', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                            ['name' => 'CEFR English Test', 'icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 11.37 9.188 15.287 3 19l2 2'],
                            ['name' => 'Simulasi Wawancara', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z']
                        ];
                    @endphp

                    @foreach($menus as $menu)
                        <div class="flex items-center gap-3 px-4 py-3 rounded-xl hover:bg-white/5 border border-transparent hover:border-white/10 text-white/50 hover:text-white/80 transition-all group cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $menu['icon'] }}"></path></svg>
                            <span class="text-sm font-medium">{{ $menu['name'] }}</span>
                            <span class="ml-auto text-[10px] bg-white/5 px-1.5 py-0.5 rounded border border-white/10 opacity-60">SOON</span>
                        </div>
                    @endforeach
                </nav>

                <div class="absolute bottom-8 left-8 right-8">
                    <div class="p-4 rounded-2xl glass border border-white/5 bg-gradient-to-br from-primary-500/5 to-transparent">
                        <p class="text-xs text-white/40 mb-3">Powered by</p>
                        <p class="text-sm font-bold tracking-tight">Maxy AI Engine v2.0</p>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-72 flex-1 p-8">
            <header class="flex justify-between items-center mb-10 animate-fade-in" style="animation-delay: 100ms">
                <div>
                    <h2 class="text-3xl font-bold tracking-tight">@yield('header_title')</h2>
                    <p class="text-white/40 mt-1">@yield('header_subtitle')</p>
                </div>
                <div class="flex gap-4">
                    <button class="w-10 h-10 rounded-xl glass border border-white/10 flex items-center justify-center hover:bg-white/5 transition-all">
                        <svg class="w-5 h-5 text-white/60" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                    </button>
                    <div class="w-10 h-10 rounded-xl bg-primary-500 flex items-center justify-center font-bold shadow-lg shadow-primary-500/20">
                        Y
                    </div>
                </div>
            </header>

            @yield('content')
        </main>
    </div>
</body>
</html>
