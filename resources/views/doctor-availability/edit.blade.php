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

    $pageTitle = $isOwnSchedule ? 'My Availability Schedule' : "Availability: {$doctor->name}";
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => 'Configure standard weekly hours and appointment slot duration.',
    'context' => 'Set recurring shifts for each day of the week. These hours determine bookable appointment slots.',
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
                    class="rounded-lg bg-white px-4 py-2 text-xs font-bold text-slate-900 shadow-xs"
                >
                    Weekly Recurring Schedule
                </a>
                <a
                    href="{{ route('doctor-availability.exceptions', $doctor) }}"
                    class="rounded-lg px-4 py-2 text-xs font-bold text-slate-600 transition hover:text-slate-900"
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

        {{-- Schedule Form --}}
        <form method="POST" action="{{ route('doctor-availability.update', $doctor) }}" class="space-y-6">
            @csrf
            @method('PUT')

            {{-- Global Slot Duration Setting --}}
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <div>
                        <label for="slot_duration_minutes" class="text-sm font-extrabold text-slate-900">
                            Appointment Slot Duration
                        </label>
                        <p class="mt-0.5 text-xs font-medium text-slate-500">
                            Each working day will be segmented into bookable slots of this length.
                        </p>
                    </div>
                    <div class="w-full sm:w-48">
                        <select
                            id="slot_duration_minutes"
                            name="slot_duration_minutes"
                            class="w-full rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-xs focus:border-green-600 focus:ring-green-600"
                        >
                            <option value="15" @selected(old('slot_duration_minutes', $slotDuration) == 15)>15 minutes</option>
                            <option value="30" @selected(old('slot_duration_minutes', $slotDuration) == 30)>30 minutes (Standard)</option>
                            <option value="45" @selected(old('slot_duration_minutes', $slotDuration) == 45)>45 minutes</option>
                            <option value="60" @selected(old('slot_duration_minutes', $slotDuration) == 60)>60 minutes (1 hour)</option>
                        </select>
                        @error('slot_duration_minutes')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                </div>
            </div>

            {{-- 7 Days Configuration Card --}}
            <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-base font-extrabold text-slate-900">Weekly Shift Template</h3>
                    <p class="mt-1 text-xs font-medium text-slate-500">Toggle active days and specify standard consulting hours for each day.</p>
                </div>

                <div class="divide-y divide-slate-100">
                    @foreach ($days as $dayIndex => $day)
                        <div
                            x-data="{ active: {{ old("days.{$dayIndex}.is_active", $day['is_active']) ? 'true' : 'false' }} }"
                            class="flex flex-col gap-4 px-5 py-4 transition sm:flex-row sm:items-center sm:justify-between"
                            :class="active ? 'bg-white' : 'bg-slate-50/70'"
                        >
                            {{-- Day Info & Toggle --}}
                            <div class="flex items-center gap-3">
                                <label class="relative inline-flex cursor-pointer items-center">
                                    <input
                                        type="checkbox"
                                        name="days[{{ $dayIndex }}][is_active]"
                                        value="1"
                                        x-model="active"
                                        class="peer sr-only"
                                    />
                                    <div class="peer h-6 w-11 rounded-full bg-slate-200 after:absolute after:left-[2px] after:top-[2px] after:h-5 after:w-5 after:rounded-full after:border after:border-slate-300 after:bg-white after:transition-all after:content-[''] peer-checked:bg-green-600 peer-checked:after:translate-x-full peer-checked:after:border-white peer-focus:outline-none peer-focus:ring-2 peer-focus:ring-green-500 peer-focus:ring-offset-2"></div>
                                </label>

                                <div>
                                    <p class="text-sm font-extrabold" :class="active ? 'text-slate-900' : 'text-slate-400'">
                                        {{ $day['day_name'] }}
                                    </p>
                                    <p class="text-xs font-medium" :class="active ? 'text-green-700' : 'text-slate-400'">
                                        <span x-text="active ? 'Available for appointments' : 'Unavailable (Off duty)'"></span>
                                    </p>
                                </div>
                            </div>

                            {{-- Working Hours Inputs --}}
                            <div class="flex items-center gap-3" x-show="active" x-transition>
                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-500">From</label>
                                    <input
                                        type="time"
                                        name="days[{{ $dayIndex }}][start_time]"
                                        value="{{ old("days.{$dayIndex}.start_time", $day['start_time']) }}"
                                        class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-xs focus:border-green-600 focus:ring-green-600"
                                    />
                                </div>

                                <span class="text-slate-300">—</span>

                                <div class="flex items-center gap-2">
                                    <label class="text-xs font-bold text-slate-500">To</label>
                                    <input
                                        type="time"
                                        name="days[{{ $dayIndex }}][end_time]"
                                        value="{{ old("days.{$dayIndex}.end_time", $day['end_time']) }}"
                                        class="rounded-lg border-slate-300 text-sm font-semibold text-slate-800 shadow-xs focus:border-green-600 focus:ring-green-600"
                                    />
                                </div>
                            </div>

                            {{-- Hidden inputs when inactive to pass validation safely --}}
                            <template x-if="! active">
                                <div>
                                    <input type="hidden" name="days[{{ $dayIndex }}][start_time]" value="08:00" />
                                    <input type="hidden" name="days[{{ $dayIndex }}][end_time]" value="17:00" />
                                    <span class="text-xs font-medium text-slate-400">No shift scheduled</span>
                                </div>
                            </template>
                        </div>
                    @endforeach
                </div>

                <div class="flex items-center justify-between border-t border-slate-100 bg-slate-50 px-5 py-4">
                    <p class="text-xs font-medium text-slate-500">
                        Changes will immediately apply to future unbooked appointment availability.
                    </p>

                    <button
                        type="submit"
                        class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-bold text-white shadow-sm transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2"
                    >
                        Save Weekly Schedule
                    </button>
                </div>
            </div>
        </form>
    </div>
@endsection

