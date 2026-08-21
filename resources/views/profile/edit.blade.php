@php
    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->map(fn ($part) => mb_substr($part, 0, 1))
        ->take(2)
        ->implode('') ?: 'RH';

    $avatarUrl = $user->avatar_path && Illuminate\Support\Facades\Storage::disk('public')->exists($user->avatar_path)
        ? asset('storage/' . $user->avatar_path)
        : 'data:image/svg+xml;utf8,' . rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 160 160"><rect width="160" height="160" rx="80" fill="#ecfdf5"/><text x="80" y="92" text-anchor="middle" font-family="Arial, sans-serif" font-size="46" font-weight="700" fill="#047857">' . htmlspecialchars($initials, ENT_QUOTES, 'UTF-8') . '</text></svg>');
@endphp

<x-app-layout>
    <x-slot name="header">
        <div>
            <p class="text-sm font-medium uppercase tracking-wide text-emerald-700">{{ __('Account Settings') }}</p>
            <h2 class="mt-1 text-2xl font-semibold tracking-tight text-slate-900">
                {{ __('Profile') }}
            </h2>
        </div>
    </x-slot>

    <div class="bg-slate-50 py-8">
        <div
            x-data="{ avatarPreview: @js($avatarUrl) }"
            class="mx-auto grid max-w-7xl grid-cols-1 gap-6 px-4 sm:px-6 lg:px-8 md:grid-cols-3"
        >
            <aside class="card-hover rounded-xl border border-slate-100 bg-white p-6 text-center shadow-sm shadow-slate-200/60">
                <div class="mx-auto w-fit">
                    <label for="avatar-input" class="block">
                        <span class="relative mx-auto block h-24 w-24 cursor-pointer overflow-hidden rounded-full bg-emerald-50 ring-4 ring-white shadow-sm group">
                            <img
                                x-bind:src="avatarPreview"
                                src="{{ $avatarUrl }}"
                                alt="{{ __('Profile picture for :name', ['name' => $user->name]) }}"
                                class="h-full w-full object-cover"
                            >
                            <span class="absolute inset-0 flex cursor-pointer items-center justify-center bg-slate-900/40 opacity-0 transition-opacity group-hover:opacity-100">
                                <svg class="h-7 w-7 text-white" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.8" stroke="currentColor" aria-hidden="true">
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M6.827 6.175A2.31 2.31 0 0 1 9.06 4.5h5.88a2.31 2.31 0 0 1 2.233 1.675l.26.91a1.125 1.125 0 0 0 1.081.815h.736A2.25 2.25 0 0 1 21.5 10.15v6.1a2.25 2.25 0 0 1-2.25 2.25H4.75a2.25 2.25 0 0 1-2.25-2.25v-6.1A2.25 2.25 0 0 1 4.75 7.9h.736a1.125 1.125 0 0 0 1.081-.815l.26-.91Z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 13.5a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0Z" />
                                </svg>
                            </span>
                        </span>
                    </label>

                    <input
                        type="file"
                        id="avatar-input"
                        name="avatar"
                        form="profile-information-form"
                        class="hidden"
                        accept="image/*"
                        x-on:change="const file = $event.target.files[0]; if (file) avatarPreview = URL.createObjectURL(file)"
                    >
                </div>

                <h3 class="mt-5 text-lg font-semibold text-slate-900">{{ $user->name }}</h3>
                <p class="mt-1 text-sm text-slate-500">{{ $user->email }}</p>

                <div class="mt-5 rounded-lg border border-emerald-100 bg-emerald-50 px-4 py-3">
                    <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700">{{ __('RHUConnect Profile') }}</p>
                    <p class="mt-1 text-sm text-emerald-900">{{ optional($user->role)->name ?? __('Healthcare Staff') }}</p>
                </div>

                <x-input-error class="mt-4 text-left" :messages="$errors->get('avatar')" />
            </aside>

            <div class="space-y-6 md:col-span-2">
                <div class="card-hover rounded-xl border border-slate-100 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                    @include('profile.partials.update-profile-information-form')
                </div>

                <div class="card-hover rounded-xl border border-slate-100 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                    @include('profile.partials.update-password-form')
                </div>

                <div class="card-hover rounded-xl border border-rose-100 bg-white p-6 shadow-sm shadow-slate-200/60 sm:p-8">
                    @include('profile.partials.delete-user-form')
                </div>
            </div>
        </div>
    </div>
</x-app-layout>
