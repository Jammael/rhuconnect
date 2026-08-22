@php
    $roleLabel = auth()->user()->role?->name ?? 'Staff';
    $isAdmin = auth()->user()->hasRole('Administrator');
    $isOwnSchedule = auth()->id() === $doctor->id;

    $navGroups = match ($roleLabel) {
        'Administrator' => [
            'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
            'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
        ],
        'Doctor' => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'My Appointments', 'My Availability', 'Patient Visit History', 'Profile']],
        default => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Profile']],
    };

    $pageTitle = $isOwnSchedule ? 'My Availability Exceptions' : "Exceptions: {$doctor->name}";
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => 'Manage leaves, holidays, and specific date schedule overrides.',
    'context' => 'Exceptions override the normal weekly recurring schedule for specific calendar dates.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Doctor Availability', 'link' => route('doctor-availability.index')],
        ['label' => $pageTitle],
    ],
    'portalLabel' => $isAdmin ? 'Admin Portal' : 'Doctor Portal',
    'roleLabel' => $roleLabel,
    'navGroups' => $navGroups,
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        @if (session('status'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: @json(session('status')), type: 'success' },
                    }));
                });
            </script>
        @endif

        {{-- Top Navigation & Mode Switcher --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex items-center gap-2 rounded-xl bg-slate-200/60 p-1">
                <a
                    href="{{ route('doctor-availability.edit', $doctor) }}"
                    class="rounded-lg px-4 py-2 text-xs font-bold text-slate-600 transition hover:text-slate-900"
                >
                    Weekly Recurring Schedule
                </a>
                <a
                    href="{{ route('doctor-availability.exceptions', $doctor) }}"
                    class="rounded-lg bg-white px-4 py-2 text-xs font-bold text-slate-900 shadow-xs"
                >
                    Date Exceptions & Overrides
                </a>
            </div>

            @if ($isAdmin)
                <a
                    href="{{ route('doctor-availability.index') }}"
                    class="inline-flex items-center gap-1.5 text-xs font-bold text-slate-500 transition hover:text-slate-800"
                >
                    ← Back to Doctors List
                </a>
            @endif
        </div>

        {{-- Add Exception Form Card --}}
        <div class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 pb-4">
                <h3 class="text-base font-extrabold text-slate-900">Add Date Exception / Override</h3>
                <p class="mt-0.5 text-xs font-medium text-slate-500">Record a leave date (fully unavailable) or a date with custom consulting hours.</p>
            </div>

            <form
                method="POST"
                action="{{ route('doctor-availability.exceptions.store', $doctor) }}"
                class="mt-4 space-y-4"
                x-data="{ isAvailable: '0' }"
            >
                @csrf

                <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {{-- Target Date --}}
                    <div>
                        <label for="date" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Date</label>
                        <div class="mt-1">
                            <x-date-picker
                                name="date"
                                id="date"
                                :value="old('date', now()->format('Y-m-d'))"
                                :min="now()->format('Y-m-d')"
                                required
                            />
                        </div>
                        @error('date')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Exception Type / Availability Mode --}}
                    <div>
                        <label for="is_available" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Status</label>
                        <select
                            id="is_available"
                            name="is_available"
                            x-model="isAvailable"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-xs focus:border-green-600 focus:ring-green-600"
                        >
                            <option value="0">Unavailable (Leave / Holiday / Off)</option>
                            <option value="1">Available (Custom Hours Override)</option>
                        </select>
                        @error('is_available')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    {{-- Reason / Description --}}
                    <div class="sm:col-span-2">
                        <label for="reason" class="block text-xs font-bold uppercase tracking-wide text-slate-500">Reason (Optional)</label>
                        <input
                            type="text"
                            id="reason"
                            name="reason"
                            value="{{ old('reason') }}"
                            placeholder="e.g. Official Business, National Holiday, Medical Conference"
                            class="mt-1 w-full rounded-lg border-slate-300 text-sm font-medium text-slate-800 shadow-xs focus:border-green-600 focus:ring-green-600"
                        />
                        @error('reason')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>

                {{-- Custom Hours (Conditional) --}}
                <div x-show="isAvailable === '1'" x-cloak x-transition class="rounded-xl border border-green-200 bg-green-50/50 p-4">
                    <p class="text-xs font-bold text-green-900">Specify Custom Consulting Hours for this Date:</p>
                    <div class="mt-3 flex flex-wrap items-center gap-4">
                        <div class="flex items-center gap-2">
                            <label for="start_time" class="text-xs font-bold text-slate-600">Start Time</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8" /><path d="M12 7v5l3 2" /></svg>
                                <input
                                    type="time"
                                    id="start_time"
                                    name="start_time"
                                    value="{{ old('start_time', '08:00') }}"
                                    class="h-10 rounded-lg border-slate-300 bg-white pl-9 text-sm font-semibold text-slate-800 shadow-xs transition-colors duration-150 hover:border-green-300 focus:border-green-600 focus:ring-green-600"
                                />
                            </div>
                        </div>

                        <span class="text-slate-400">—</span>

                        <div class="flex items-center gap-2">
                            <label for="end_time" class="text-xs font-bold text-slate-600">End Time</label>
                            <div class="relative">
                                <svg class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-green-700" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="8" /><path d="M12 7v5l3 2" /></svg>
                                <input
                                    type="time"
                                    id="end_time"
                                    name="end_time"
                                    value="{{ old('end_time', '12:00') }}"
                                    class="h-10 rounded-lg border-slate-300 bg-white pl-9 text-sm font-semibold text-slate-800 shadow-xs transition-colors duration-150 hover:border-green-300 focus:border-green-600 focus:ring-green-600"
                                />
                            </div>
                        </div>
                    </div>
                    @error('start_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                    @error('end_time')
                        <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <div class="flex justify-end pt-2">
                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2 text-sm font-bold text-white shadow-sm transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        Save Exception
                    </button>
                </div>
            </form>
        </div>

        {{-- Existing Exceptions List --}}
        <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-extrabold text-slate-900">Recorded Exceptions & Overrides</h3>
                <p class="mt-0.5 text-xs font-medium text-slate-500">List of active and historical date-specific availability overrides for this doctor.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Override Status</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Reason</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Action</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($exceptions as $exception)
                            <tr class="card-hover">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-extrabold text-slate-900">
                                    {{ $exception->date->format('M d, Y / l') }}
                                    @if ($exception->date->isPast() && ! $exception->date->isToday())
                                        <span class="ml-1.5 text-xs font-semibold text-slate-400">(Past)</span>
                                    @elseif ($exception->date->isToday())
                                        <span class="ml-1.5 text-xs font-bold text-green-700">(Today)</span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if (! $exception->is_available)
                                        <span class="inline-flex items-center rounded-full bg-red-100 px-2.5 py-1 text-xs font-extrabold text-red-800">
                                            Unavailable (Off)
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-blue-100 px-2.5 py-1 text-xs font-extrabold text-blue-800">
                                            Custom: {{ substr($exception->start_time, 0, 5) }} - {{ substr($exception->end_time, 0, 5) }}
                                        </span>
                                    @endif
                                </td>
                                <td class="px-5 py-4 text-sm font-medium text-slate-600">
                                    {{ $exception->reason ?: '—' }}
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right">
                                    <form
                                        method="POST"
                                        action="{{ route('doctor-availability.exceptions.destroy', [$doctor, $exception]) }}"
                                        onsubmit="return confirm('Remove this date exception?');"
                                    >
                                        @csrf
                                        @method('DELETE')
                                        <button
                                            type="submit"
                                            class="rounded-lg px-2.5 py-1 text-xs font-bold text-red-600 transition hover:bg-red-50 hover:text-red-800"
                                        >
                                            Delete
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm font-medium text-slate-500">
                                    No date exceptions recorded yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($exceptions->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $exceptions->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
