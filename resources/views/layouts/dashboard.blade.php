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
                            <div
                                class="relative"
                                x-data="{
                                    open: false,
                                    notifications: [],
                                    unreadCount: 0,
                                    csrfToken: document.querySelector('meta[name=csrf-token]').content,
                                    iconPaths: {
                                        'user-check': '<path d=&quot;M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2&quot; /><circle cx=&quot;9&quot; cy=&quot;7&quot; r=&quot;4&quot; /><path d=&quot;m16 11 2 2 4-4&quot; />',
                                        'user-x': '<path d=&quot;M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2&quot; /><circle cx=&quot;9&quot; cy=&quot;7&quot; r=&quot;4&quot; /><path d=&quot;m17 8 5 5&quot; /><path d=&quot;m22 8-5 5&quot; />',
                                        'user-plus': '<path d=&quot;M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2&quot; /><circle cx=&quot;9&quot; cy=&quot;7&quot; r=&quot;4&quot; /><path d=&quot;M19 8v6&quot; /><path d=&quot;M22 11h-6&quot; />',
                                        'shield-alert': '<path d=&quot;M20 13c0 5-3.5 7.5-7.7 8.9a1 1 0 0 1-.6 0C7.5 20.5 4 18 4 13V6a1 1 0 0 1 1-1c2 0 4.5-1.2 6.2-2.4a1.4 1.4 0 0 1 1.6 0C14.5 3.8 17 5 19 5a1 1 0 0 1 1 1z&quot; /><path d=&quot;M12 8v4&quot; /><path d=&quot;M12 16h.01&quot; />',
                                        bell: '<path d=&quot;M10.3 21a1.9 1.9 0 0 0 3.4 0&quot; /><path d=&quot;M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9&quot; />',
                                    },
                                    init() {
                                        this.fetchNotifications();
                                        setInterval(() => this.fetchNotifications(), 30000);
                                    },
                                    async fetchNotifications() {
                                        const response = await fetch('{{ route('notifications.index') }}', {
                                            headers: {
                                                'Accept': 'application/json',
                                                'X-Background-Poll': 'true',
                                            },
                                        });

                                        if (! response.ok) {
                                            return;
                                        }

                                        if (response.redirected) {
                                            window.location.href = response.url;

                                            return;
                                        }

                                        if (! response.headers.get('content-type')?.includes('application/json')) {
                                            return;
                                        }

                                        const data = await response.json();
                                        this.notifications = data.notifications ?? [];
                                        this.unreadCount = data.unread_count ?? 0;
                                    },
                                    async markAsRead(notification) {
                                        const wasUnread = ! notification.read_at;

                                        if (wasUnread) {
                                            notification.read_at = new Date().toISOString();
                                            this.unreadCount = Math.max(this.unreadCount - 1, 0);
                                        }

                                        await fetch(`/notifications/${notification.id}/read`, {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': this.csrfToken,
                                            },
                                        });

                                        if (notification.link) {
                                            window.location.href = notification.link;
                                        } else {
                                            this.fetchNotifications();
                                        }
                                    },
                                    async markAllAsRead() {
                                        this.notifications = this.notifications.map((notification) => ({
                                            ...notification,
                                            read_at: notification.read_at ?? new Date().toISOString(),
                                        }));
                                        this.unreadCount = 0;

                                        await fetch('{{ route('notifications.read-all') }}', {
                                            method: 'POST',
                                            headers: {
                                                'Accept': 'application/json',
                                                'Content-Type': 'application/json',
                                                'X-CSRF-TOKEN': this.csrfToken,
                                            },
                                        });
                                    },
                                    relativeTime(date) {
                                        const seconds = Math.max(Math.floor((Date.now() - new Date(date).getTime()) / 1000), 0);
                                        const intervals = [
                                            ['year', 31536000],
                                            ['month', 2592000],
                                            ['day', 86400],
                                            ['hour', 3600],
                                            ['minute', 60],
                                        ];

                                        for (const [unit, value] of intervals) {
                                            const count = Math.floor(seconds / value);

                                            if (count >= 1) {
                                                return `${count} ${unit}${count === 1 ? '' : 's'} ago`;
                                            }
                                        }

                                        return 'Just now';
                                    },
                                    iconFor(notification) {
                                        return this.iconPaths[notification.icon] ?? this.iconPaths.bell;
                                    },
                                }"
                            >
                                <button
                                    class="relative rounded-xl border border-slate-200 bg-white p-2.5 text-slate-500 shadow-sm transition hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 focus:ring-offset-slate-50"
                                    type="button"
                                    aria-label="Notifications"
                                    x-on:click="open = ! open"
                                    x-bind:aria-expanded="open.toString()"
                                >
                                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M10.3 21a1.9 1.9 0 0 0 3.4 0" />
                                        <path d="M18 8A6 6 0 0 0 6 8c0 7-3 7-3 9h18c0-2-3-2-3-9" />
                                    </svg>
                                    <span x-cloak x-show="unreadCount > 0" class="absolute right-2 top-2 h-2.5 w-2.5 rounded-full border-2 border-white bg-red-500"></span>
                                </button>

                                <div
                                    x-cloak
                                    x-show="open"
                                    x-transition:enter="transition ease-out duration-150"
                                    x-transition:enter-start="translate-y-1 opacity-0"
                                    x-transition:enter-end="translate-y-0 opacity-100"
                                    x-transition:leave="transition ease-in duration-100"
                                    x-transition:leave-start="translate-y-0 opacity-100"
                                    x-transition:leave-end="translate-y-1 opacity-0"
                                    x-on:click.outside="open = false"
                                    class="absolute right-0 z-50 mt-2 w-[22rem] max-w-[calc(100vw-2rem)] overflow-hidden rounded-xl border border-slate-200 bg-white shadow-xl shadow-slate-200/70"
                                >
                                    <div class="flex items-center justify-between gap-4 border-b border-slate-100 px-4 py-3">
                                        <h2 class="text-sm font-extrabold text-slate-900">Notifications</h2>
                                        <button
                                            type="button"
                                            class="text-xs font-bold text-green-700 transition hover:text-green-900 disabled:cursor-default disabled:text-slate-300"
                                            x-on:click="markAllAsRead()"
                                            x-bind:disabled="unreadCount === 0"
                                        >
                                            Mark all as read
                                        </button>
                                    </div>

                                    <div class="max-h-96 overflow-y-auto p-2">
                                        <template x-if="notifications.length === 0">
                                            <div class="px-4 py-8 text-center text-sm font-medium text-slate-500">
                                                No notifications yet
                                            </div>
                                        </template>

                                        <template x-for="notification in notifications" x-bind:key="notification.id">
                                            <button
                                                type="button"
                                                class="flex w-full gap-3 rounded-lg px-3 py-3 text-left transition hover:bg-slate-50"
                                                x-on:click="markAsRead(notification)"
                                            >
                                                <span class="mt-0.5 flex h-9 w-9 shrink-0 items-center justify-center rounded-xl bg-green-50 text-green-700">
                                                    <svg class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" x-html="iconFor(notification)"></svg>
                                                </span>
                                                <span class="min-w-0 flex-1">
                                                    <span class="flex items-start justify-between gap-3">
                                                        <span class="text-sm font-semibold leading-5 text-slate-800" x-text="notification.message"></span>
                                                        <span x-show="! notification.read_at" class="mt-1.5 h-2 w-2 shrink-0 rounded-full bg-red-500"></span>
                                                    </span>
                                                    <span class="mt-1 block text-xs font-medium text-slate-400" x-text="relativeTime(notification.created_at)"></span>
                                                </span>
                                            </button>
                                        </template>
                                    </div>
                                </div>
                            </div>
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

        <div
            x-data="{
                toasts: [],
                addToast(event) {
                    const toast = {
                        id: Date.now() + Math.random(),
                        type: event.detail.type ?? 'success',
                        message: event.detail.message ?? '',
                    };

                    this.toasts.push(toast);
                    setTimeout(() => this.removeToast(toast.id), 4000);
                },
                removeToast(id) {
                    this.toasts = this.toasts.filter((toast) => toast.id !== id);
                },
                accentClass(type) {
                    return {
                        success: 'border-green-500',
                        error: 'border-red-500',
                        warning: 'border-amber-500',
                    }[type] ?? 'border-green-500';
                },
            }"
            x-init="window.addEventListener('toast', (event) => addToast(event))"
            class="fixed bottom-4 right-4 z-50 w-[22rem] max-w-[calc(100vw-2rem)] space-y-2"
            aria-live="polite"
        >
            <template x-for="toast in toasts" x-bind:key="toast.id">
                <div
                    x-transition:enter="transition ease-out duration-200"
                    x-transition:enter-start="translate-y-2 opacity-0"
                    x-transition:enter-end="translate-y-0 opacity-100"
                    x-transition:leave="transition ease-in duration-150"
                    x-transition:leave-start="translate-y-0 opacity-100"
                    x-transition:leave-end="translate-y-2 opacity-0"
                    class="rounded-lg border border-l-4 border-slate-200 bg-white px-4 py-3 shadow-lg shadow-slate-200/80"
                    x-bind:class="accentClass(toast.type)"
                >
                    <div class="flex items-start justify-between gap-3">
                        <p class="text-sm font-semibold leading-5 text-slate-800" x-text="toast.message"></p>
                        <button type="button" class="shrink-0 text-slate-400 transition hover:text-slate-700" x-on:click="removeToast(toast.id)" aria-label="Dismiss notification">
                            <svg class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M18 6 6 18" />
                                <path d="m6 6 12 12" />
                            </svg>
                        </button>
                    </div>
                </div>
            </template>
        </div>

        <div
            x-data="{
                visible: false,
                countdown: 60,
                warningTimer: null,
                countdownTimer: null,
                lastHeartbeatAt: 0,
                csrfToken: document.querySelector('meta[name=csrf-token]').content,
                lifetimeSeconds: @js(max((int) config('session.lifetime'), 1) * 60),
                warningSeconds: 60,
                init() {
                    ['mousemove', 'keydown', 'click', 'touchstart'].forEach((eventName) => {
                        window.addEventListener(eventName, () => this.resetIdleTimer(), { passive: true });
                    });

                    this.startWarningTimer();
                },
                startWarningTimer() {
                    clearTimeout(this.warningTimer);
                    this.warningTimer = setTimeout(
                        () => this.showWarning(),
                        Math.max((this.lifetimeSeconds - this.warningSeconds) * 1000, 0),
                    );
                },
                resetIdleTimer() {
                    if (this.visible) {
                        this.visible = false;
                        clearInterval(this.countdownTimer);
                    }

                    this.sendHeartbeat();
                    this.startWarningTimer();
                },
                showWarning() {
                    this.visible = true;
                    this.countdown = this.warningSeconds;
                    clearInterval(this.countdownTimer);
                    this.countdownTimer = setInterval(() => {
                        this.countdown -= 1;

                        if (this.countdown <= 0) {
                            clearInterval(this.countdownTimer);
                            window.location.href = window.location.href;
                        }
                    }, 1000);
                },
                async sendHeartbeat() {
                    if (Date.now() - this.lastHeartbeatAt < 30000) {
                        return;
                    }

                    this.lastHeartbeatAt = Date.now();

                    await fetch('{{ route('session.heartbeat') }}', {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': this.csrfToken,
                        },
                    }).then((response) => {
                        if (response.redirected) {
                            window.location.href = response.url;
                        }
                    });
                },
            }"
            x-cloak
            x-show="visible"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="translate-y-2 opacity-0"
            x-transition:enter-end="translate-y-0 opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="translate-y-0 opacity-100"
            x-transition:leave-end="translate-y-2 opacity-0"
            class="fixed inset-x-4 bottom-4 z-50 mx-auto max-w-xl rounded-xl border border-amber-200 bg-white p-4 shadow-xl shadow-slate-200/80 sm:bottom-6"
            role="alert"
        >
            <div class="flex items-start gap-3">
                <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-amber-100 text-amber-700">
                    <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M10 2h4" />
                        <path d="M12 14v-4" />
                        <path d="M12 22a8 8 0 1 0 0-16 8 8 0 0 0 0 16Z" />
                    </svg>
                </div>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-extrabold text-slate-900">You'll be signed out in <span x-text="countdown"></span> seconds due to inactivity.</p>
                    <p class="mt-1 text-sm font-medium leading-5 text-slate-500">Move your mouse or click anywhere to stay signed in.</p>
                </div>
            </div>
        </div>

        <script>
            (() => {
                const closeUrl = @json(route('session.close-beacon'));
                const csrfToken = document.querySelector('meta[name=csrf-token]')?.content;
                const navigationIntentKey = 'rhu_navigation_intent_until';
                const graceMs = 2000;

                if (! csrfToken || ! navigator.sendBeacon) {
                    return;
                }

                const markNavigationIntent = () => {
                    sessionStorage.setItem(navigationIntentKey, String(Date.now() + graceMs));
                };

                const hasNavigationIntent = () => {
                    return Number(sessionStorage.getItem(navigationIntentKey) || 0) > Date.now();
                };

                window.addEventListener('DOMContentLoaded', () => {
                    sessionStorage.removeItem(navigationIntentKey);
                });

                document.addEventListener('click', (event) => {
                    const link = event.target.closest?.('a[href]');

                    if (! link || event.defaultPrevented || event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
                        return;
                    }

                    const url = new URL(link.href, window.location.href);

                    if (url.origin === window.location.origin && link.target !== '_blank' && ! link.hasAttribute('download')) {
                        markNavigationIntent();
                    }
                }, true);

                document.addEventListener('submit', (event) => {
                    const form = event.target;
                    const action = new URL(form.action || window.location.href, window.location.href);

                    if (action.origin === window.location.origin) {
                        markNavigationIntent();
                    }
                }, true);

                window.addEventListener('pagehide', (event) => {
                    if (event.persisted) {
                        return;
                    }

                    if (hasNavigationIntent()) {
                        return;
                    }

                    const body = new URLSearchParams();
                    body.append('_token', csrfToken);

                    navigator.sendBeacon(
                        closeUrl,
                        new Blob([body.toString()], { type: 'application/x-www-form-urlencoded;charset=UTF-8' }),
                    );
                });
            })();
        </script>
    </body>
</html>
