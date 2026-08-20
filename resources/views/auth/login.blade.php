<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>Sign In - {{ config('app.name', 'RHUConnect') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600,700,800&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans text-gray-900 antialiased">
        <main class="flex min-h-screen items-center justify-center bg-gradient-to-br from-green-50 to-white px-4 py-8">
            <section class="w-full max-w-md rounded-2xl bg-white p-8 shadow-xl shadow-slate-200/80 sm:p-10">
                <div class="text-center">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-green-600 text-white shadow-lg shadow-green-600/20" aria-hidden="true">
                        <svg class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M12 5v14" />
                            <path d="M5 12h14" />
                        </svg>
                    </div>
                    <p class="mt-4 text-lg font-extrabold leading-none text-green-700">RHUConnect</p>
                    <p class="mt-1 text-xs font-medium text-gray-500">Sierra Bullones Bohol</p>
                </div>

                <div class="mt-8 text-center">
                    <h1 class="text-2xl font-bold text-gray-900">Welcome back</h1>
                    <p class="mt-2 text-sm text-gray-500">Sign in to access your RHUConnect account.</p>
                </div>

                <x-auth-session-status class="mt-6 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800" :status="session('status')" />

                <form class="mt-8 space-y-5" method="POST" action="{{ route('login') }}" x-data="{ showPassword: false, submitting: false }" x-on:submit="submitting = true">
                    @csrf

                    <div>
                        <label for="email" class="block text-sm font-semibold text-gray-700">Email Address</label>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-gray-400" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="20" height="16" x="2" y="4" rx="2" />
                                    <path d="m22 7-8.97 5.7a2 2 0 0 1-2.06 0L2 7" />
                                </svg>
                            </span>
                            <input
                                id="email"
                                class="block h-12 w-full rounded-lg border border-gray-300 bg-white pl-11 pr-4 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                type="email"
                                name="email"
                                value="{{ old('email') }}"
                                required
                                autofocus
                                autocomplete="username"
                                placeholder="Enter your email address"
                                aria-describedby="@error('email') email-error @enderror"
                            >
                        </div>
                        @error('email')
                            <p id="email-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <div class="flex items-center justify-between gap-4">
                            <label for="password" class="block text-sm font-semibold text-gray-700">Password</label>
                            @if (Route::has('password.request'))
                                <a class="text-sm font-semibold text-green-600 transition hover:text-green-700 focus:rounded focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2" href="{{ route('password.request') }}">
                                    Forgot Password?
                                </a>
                            @endif
                        </div>
                        <div class="relative mt-2">
                            <span class="pointer-events-none absolute inset-y-0 left-0 flex w-11 items-center justify-center text-gray-400" aria-hidden="true">
                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <rect width="18" height="11" x="3" y="11" rx="2" />
                                    <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                                </svg>
                            </span>
                            <input
                                id="password"
                                class="block h-12 w-full rounded-lg border border-gray-300 bg-white pl-11 pr-11 text-sm text-gray-900 outline-none transition placeholder:text-gray-400 focus:border-green-500 focus:ring-2 focus:ring-green-500"
                                x-bind:type="showPassword ? 'text' : 'password'"
                                name="password"
                                required
                                autocomplete="current-password"
                                placeholder="Enter your password"
                                aria-describedby="@error('password') password-error @enderror"
                            >
                            <button
                                class="absolute inset-y-0 right-0 flex w-11 items-center justify-center rounded-r-lg text-gray-400 transition hover:text-green-600 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-green-500"
                                type="button"
                                x-on:click="showPassword = ! showPassword"
                                x-bind:aria-label="showPassword ? 'Hide password' : 'Show password'"
                            >
                                <svg x-show="! showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M2.06 12.35a1 1 0 0 1 0-.7 11 11 0 0 1 19.88 0 1 1 0 0 1 0 .7 11 11 0 0 1-19.88 0" />
                                    <circle cx="12" cy="12" r="3" />
                                </svg>
                                <svg x-cloak x-show="showPassword" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="m2 2 20 20" />
                                    <path d="M6.7 6.7A10.9 10.9 0 0 0 2.06 11.65a1 1 0 0 0 0 .7 11 11 0 0 0 15.24 5.3" />
                                    <path d="M10.8 10.8A3 3 0 0 0 15.2 15.2" />
                                    <path d="M12 5c4.4 0 8.1 2.6 9.94 6.65a1 1 0 0 1 0 .7 10.8 10.8 0 0 1-2.1 3.1" />
                                </svg>
                            </button>
                        </div>
                        @error('password')
                            <p id="password-error" class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button
                        type="submit"
                        class="flex h-11 w-full items-center justify-center rounded-lg bg-green-600 px-4 font-semibold text-white transition-colors hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2 disabled:cursor-not-allowed disabled:bg-green-500"
                        x-bind:disabled="submitting"
                    >
                        <span x-show="! submitting">Sign In</span>
                        <span x-cloak x-show="submitting" class="inline-flex items-center gap-2">
                            <svg class="h-4 w-4 animate-spin" viewBox="0 0 24 24" fill="none" aria-hidden="true">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v4a4 4 0 0 0-4 4H4z"></path>
                            </svg>
                            Signing in
                        </span>
                    </button>
                </form>

                <p class="mt-6 flex items-center justify-center gap-1.5 text-center text-xs text-gray-400">
                    <svg class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <rect width="18" height="11" x="3" y="11" rx="2" />
                        <path d="M7 11V7a5 5 0 0 1 10 0v4" />
                    </svg>
                    <span>Secured by role-based access control.</span>
                </p>
            </section>
        </main>
    </body>
</html>
