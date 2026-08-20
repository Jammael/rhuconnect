@props([
    'pageTitle' => 'Dashboard',
    'pageSubtitle' => '',
    'context' => '',
    'portalLabel' => 'Portal',
    'roleLabel' => '',
    'navGroups' => [],
    'user' => auth()->user(),
])

@php
    $initials = collect(explode(' ', trim($user->name ?? 'RHU User')))
        ->filter()
        ->take(2)
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->implode('');

    $hour = now()->hour;
    $greeting = $hour < 12 ? 'Good morning' : ($hour < 18 ? 'Good afternoon' : 'Good evening');

    $navIcons = [
        'Dashboard' => '<path d="M3 10.5 12 3l9 7.5" /><path d="M5 9.5V21h14V9.5" /><path d="M9 21v-6h6v6" />',
        'Online Appointments' => '<rect width="18" height="16" x="3" y="5" rx="2" /><path d="M8 3v4" /><path d="M16 3v4" /><path d="M3 10h18" />',
        'My Appointments' => '<rect width="18" height="16" x="3" y="5" rx="2" /><path d="M8 3v4" /><path d="M16 3v4" /><path d="M8 14h.01" /><path d="M12 14h.01" /><path d="M16 14h.01" />',
        'Maternal Care Appointments' => '<rect width="18" height="16" x="3" y="5" rx="2" /><path d="M8 3v4" /><path d="M16 3v4" /><path d="M9 15h6" /><path d="M12 12v6" />',
        'Appointment Entry' => '<path d="M8 4h8" /><path d="M9 2h6v4H9z" /><path d="M6 4h-.5A2.5 2.5 0 0 0 3 6.5v12A2.5 2.5 0 0 0 5.5 21h13a2.5 2.5 0 0 0 2.5-2.5v-12A2.5 2.5 0 0 0 18.5 4H18" /><path d="M8 13h5" /><path d="M8 17h8" />',
        'Smart Queue' => '<path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M3 6h.01" /><path d="M3 12h.01" /><path d="M3 18h.01" />',
        'Patient Queue' => '<path d="M8 6h13" /><path d="M8 12h13" /><path d="M8 18h13" /><path d="M4 6h.01" /><path d="M4 12h.01" /><path d="M4 18h.01" />',
        'Doctor Availability' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="m16 11 2 2 4-4" />',
        'My Availability' => '<circle cx="9" cy="7" r="4" /><path d="M2 21v-2a4 4 0 0 1 4-4h4" /><path d="M16 17h6" /><path d="M19 14v6" />',
        'Slot Capacity' => '<path d="M4 19V5" /><path d="M8 17v-6" /><path d="M12 17V7" /><path d="M16 17v-4" /><path d="M20 17V9" />',
        'Patient Visit History' => '<path d="M8 4h8" /><path d="M9 2h6v4H9z" /><path d="M6 4h-.5A2.5 2.5 0 0 0 3 6.5v12A2.5 2.5 0 0 0 5.5 21h13a2.5 2.5 0 0 0 2.5-2.5v-12A2.5 2.5 0 0 0 18.5 4H18" /><path d="M8 13h4" /><path d="M8 17h7" /><path d="M16 13h.01" />',
        'Visit History' => '<path d="M8 4h8" /><path d="M9 2h6v4H9z" /><path d="M6 4h-.5A2.5 2.5 0 0 0 3 6.5v12A2.5 2.5 0 0 0 5.5 21h13a2.5 2.5 0 0 0 2.5-2.5v-12A2.5 2.5 0 0 0 18.5 4H18" /><path d="M8 13h4" /><path d="M8 17h7" />',
        'Reports & Analytics' => '<path d="M3 3v18h18" /><path d="m7 15 4-4 3 3 5-7" /><path d="M19 7v5h-5" />',
        'SMS Notifications' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" /><path d="M8 9h8" /><path d="M8 13h5" />',
        'SMS Notifications Log' => '<path d="M21 15a4 4 0 0 1-4 4H8l-5 3V7a4 4 0 0 1 4-4h10a4 4 0 0 1 4 4z" /><path d="M8 9h8" /><path d="M8 13h5" />',
        'User Management' => '<path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" /><circle cx="9" cy="7" r="4" /><path d="M22 21v-2a4 4 0 0 0-3-3.87" /><path d="M16 3.13a4 4 0 0 1 0 7.75" />',
        'Profile' => '<circle cx="12" cy="8" r="4" /><path d="M4 21a8 8 0 0 1 16 0" />',
        'Patient Records' => '<path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" /><path d="M14 2v6h6" /><path d="M8 13h8" /><path d="M8 17h6" />',
        'Vitals/Triage' => '<path d="M20.8 4.6a5.5 5.5 0 0 0-7.8 0L12 5.6l-1-1a5.5 5.5 0 1 0-7.8 7.8l1 1L12 21l7.8-7.6 1-1a5.5 5.5 0 0 0 0-7.8" /><path d="M7 12h3l2-3 2 6 2-3h3" />',
    ];

    $navHref = function ($item) {
        if (is_string($item)) {
            return $item === 'Dashboard' ? route('dashboard') : ($item === 'Profile' ? route('profile.edit') : '#');
        }

        return isset($item['route']) && Route::has($item['route']) ? route($item['route']) : '#';
    };

    $navLabel = fn ($item) => is_string($item) ? $item : $item['label'];

    $navIcon = fn ($item) => $navIcons[$navLabel($item)] ?? '<circle cx="12" cy="12" r="8" /><path d="M12 8v8" /><path d="M8 12h8" />';

    $navActive = function ($item) {
        if (is_string($item)) {
            return $item === 'Dashboard'
                ? request()->routeIs('dashboard') || request()->routeIs('admin.dashboard')
                : ($item === 'Profile' && request()->routeIs('profile.*'));
        }

        return isset($item['active']) ? request()->routeIs($item['active']) : false;
    };
@endphp

<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ $pageTitle }} - {{ config('app.name', 'RHUConnect') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-slate-50 text-slate-900 lg:flex" x-data="{ sidebarOpen: false }">
            <div x-cloak x-show="sidebarOpen" class="fixed inset-0 z-40 bg-slate-900/40 lg:hidden" x-on:click="sidebarOpen = false"></div>

            <aside
                class="fixed inset-y-0 left-0 z-50 flex w-64 -translate-x-full flex-col border-r border-slate-200 bg-white shadow-xl shadow-slate-200/60 transition-transform duration-200 lg:sticky lg:top-0 lg:h-screen lg:shrink-0 lg:translate-x-0 lg:shadow-none"
                x-bind:class="sidebarOpen ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
            >
                <div class="flex h-20 items-center gap-3 border-b border-slate-100 px-6">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-green-600 text-white shadow-lg shadow-green-600/20" aria-hidden="true">
                        <svg class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                    </div>
                    <div>
                        <p class="text-base font-extrabold leading-tight text-green-700">RHUConnect</p>
                        <p class="text-xs font-semibold text-slate-400">{{ $portalLabel }}</p>
                    </div>
                </div>

                <nav class="flex-1 space-y-7 overflow-y-auto px-4 py-6">
                    @foreach ($navGroups as $group => $items)
                        <div>
                            <p class="px-3 text-[11px] font-bold uppercase tracking-wide text-slate-400">{{ $group }}</p>
                            <div class="mt-3 space-y-1">
                                @foreach ($items as $item)
                                    @php
                                        $isActive = $navActive($item);
                                    @endphp
                                    <a
                                        href="{{ $navHref($item) }}"
                                        class="group relative flex items-center gap-3 rounded-xl px-3 py-2.5 text-sm font-semibold transition {{ $isActive ? 'bg-green-50 text-green-700' : 'text-slate-500 hover:bg-slate-50 hover:text-slate-900' }}"
                                    >
                                        @if ($isActive)
                                            <span class="absolute left-0 h-7 w-1 rounded-r-full bg-green-600"></span>
                                        @endif
                                        <span class="shrink-0">
                                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                                {!! $navIcon($item) !!}
                                            </svg>
                                        </span>
                                        <span>{{ $navLabel($item) }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </nav>

                <div class="border-t border-slate-100 p-4">
                    <div class="relative flex items-center gap-3 rounded-xl bg-slate-50 p-3" x-data="{ menuOpen: false }">
                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-extrabold text-green-700">
                            {{ $initials }}
                        </div>
                        <div class="min-w-0 flex-1">
                            <p class="truncate text-sm font-bold text-slate-800">{{ $user->name }}</p>
                            <p class="truncate text-xs font-medium text-slate-500">{{ $roleLabel }}</p>
                        </div>
                        <button
                            class="rounded-lg p-1.5 text-slate-400 transition hover:bg-white hover:text-slate-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-slate-50"
                            type="button"
                            x-on:click="menuOpen = ! menuOpen"
                            aria-label="Open account menu"
                            x-bind:aria-expanded="menuOpen.toString()"
                        >
                            <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="12" r="1" />
                                <circle cx="19" cy="12" r="1" />
                                <circle cx="5" cy="12" r="1" />
                            </svg>
                        </button>

                        <div
                            x-cloak
                            x-show="menuOpen"
                            x-on:click.outside="menuOpen = false"
                            class="absolute bottom-full right-0 z-50 mb-2 w-48 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 shadow-xl shadow-slate-200/70"
                        >
                            <a href="{{ route('profile.edit') }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-green-700">
                                Profile
                            </a>
                            <form method="POST" action="{{ route('logout') }}">
                                @csrf
                                <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-red-600 transition hover:bg-red-50">
                                    Logout
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            </aside>

            <div class="min-w-0 flex-1">
                <header class="sticky top-0 z-30 border-b border-slate-200/80 bg-slate-50/95 backdrop-blur">
                    <div class="flex min-h-20 items-center justify-between gap-4 px-4 py-4 sm:px-6 lg:px-8">
                        <div class="flex items-center gap-3">
                            <button class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 shadow-sm lg:hidden" type="button" x-on:click="sidebarOpen = true" aria-label="Open navigation">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M4 6h16" />
                                    <path d="M4 12h16" />
                                    <path d="M4 18h16" />
                                </svg>
                            </button>
                            <div>
                                <h1 class="text-xl font-extrabold text-slate-900 sm:text-2xl">{{ $pageTitle }}</h1>
                                <p class="mt-1 text-sm font-medium text-slate-500">{{ $pageSubtitle }}</p>
                            </div>
                        </div>

                        <div class="flex shrink-0 items-center gap-3">
                            <button class="rounded-xl border border-slate-200 bg-white p-2.5 text-slate-500 shadow-sm transition hover:text-green-700" type="button" aria-label="Notifications">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                    <path d="M10.3 21a1.9 1.9 0 0 0 3.4 0" />
                                    <path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                </svg>
                            </button>
                            <p class="hidden text-right text-sm font-semibold text-slate-500 sm:block">{{ now()->format('F j, Y / l') }}</p>
                        </div>
                    </div>
                </header>

                <main class="px-4 py-8 sm:px-6 lg:px-8">
                    <div class="mb-8">
                        <h2 class="text-2xl font-extrabold text-slate-900 sm:text-3xl">{{ $greeting }}, {{ Str::of($user->name)->before(' ') }}</h2>
                        <p class="mt-2 max-w-3xl text-sm font-medium leading-6 text-slate-500">{{ $context }}</p>
                    </div>

                    @yield('content')
                </main>
            </div>
        </div>
    </body>
</html>
