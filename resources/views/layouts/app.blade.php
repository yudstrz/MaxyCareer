<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
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
<body class="antialiased min-h-screen bg-gray-50">
    <div class="flex">
        <!-- Sidebar -->
        <aside class="fixed left-0 top-0 h-screen w-64 bg-white border-r border-gray-200 z-50 overflow-y-auto flex flex-col">
            <div class="p-6">
                <!-- Logo -->
                <div class="flex items-center gap-3 mb-8">
                    <div class="w-9 h-9 rounded-lg bg-primary-500 flex items-center justify-center">
                        <span class="text-white font-bold text-lg">M</span>
                    </div>
                    <div>
                        <h1 class="text-lg font-bold tracking-tight text-gray-900">Maxy<span class="text-primary-500">Career</span></h1>
                        <p class="text-[10px] text-gray-400 uppercase tracking-[0.15em] font-medium">AI Career Engine</p>
                    </div>
                </div>

                <!-- Navigation -->
                <nav class="space-y-1">
                    <a href="/" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->is('/') ? 'bg-primary-50 text-primary-500 border border-primary-100' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 border border-transparent' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"></path></svg>
                        <span>CV ATS Analyst</span>
                    </a>

                    <a href="{{ route('job-matching') }}" class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium transition-all {{ request()->routeIs('job-matching') ? 'bg-primary-50 text-primary-500 border border-primary-100' : 'text-gray-500 hover:bg-gray-50 hover:text-gray-700 border border-transparent' }}">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                        <span>Job Matching</span>
                    </a>

                    @php
                        $menus = [
                            ['name' => 'Psychological Test', 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
                            ['name' => 'CEFR English Test', 'icon' => 'M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 11.37 9.188 15.287 3 19l2 2'],
                            ['name' => 'Simulasi Wawancara', 'icon' => 'M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z']
                        ];
                    @endphp

                    @foreach($menus as $menu)
                        <div class="flex items-center gap-3 px-3 py-2.5 rounded-lg text-sm font-medium text-gray-400 cursor-not-allowed">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="{{ $menu['icon'] }}"></path></svg>
                            <span>{{ $menu['name'] }}</span>
                            <span class="ml-auto badge-pill bg-gray-100 text-gray-400 text-[10px]">Soon</span>
                        </div>
                    @endforeach
                </nav>
            </div>

            <!-- Bottom Card -->
            <div class="mt-auto p-6">
                <div class="p-4 rounded-xl bg-primary-50 border border-primary-100">
                    <p class="text-[11px] text-gray-400 mb-1">Powered by</p>
                    <p class="text-sm font-bold text-gray-900 tracking-tight">Maxy AI Engine v2.0</p>
                </div>
            </div>
        </aside>

        <!-- Main Content -->
        <main class="ml-64 flex-1 min-h-screen">
            <!-- Flat Navbar -->
            <header class="sticky top-0 z-40 bg-white/80 backdrop-blur-sm border-b border-gray-200">
                <div class="flex justify-between items-center px-8 py-4">
                    <div>
                        <h2 class="text-xl font-bold tracking-tight text-gray-900">@yield('header_title')</h2>
                        <p class="text-gray-500 text-sm mt-0.5">@yield('header_subtitle')</p>
                    </div>
                    <div class="flex items-center gap-3">
                        <button class="w-9 h-9 rounded-lg border border-gray-200 flex items-center justify-center hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"></path></svg>
                        </button>
                        <div class="w-9 h-9 rounded-lg bg-primary-500 flex items-center justify-center text-white font-bold text-sm">
                            Y
                        </div>
                    </div>
                </div>
            </header>

            <!-- Page Content -->
            <div class="p-8">
                @yield('content')
            </div>
        </main>
    </div>
</body>
</html>
