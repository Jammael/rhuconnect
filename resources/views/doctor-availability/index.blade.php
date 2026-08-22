@php
    $roleLabel = auth()->user()->role?->name ?? 'Staff';
    $navGroups = match ($roleLabel) {
        'Administrator' => [
            'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
            'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
        ],
        default => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Profile']],
    };
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Doctor Availability',
    'pageSubtitle' => 'Configure recurring schedules and date exceptions for RHU doctors.',
    'context' => 'Manage doctor shift templates, appointment slot durations, and date-specific overrides for appointments.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Doctor Availability'],
    ],
    'portalLabel' => 'Admin Portal',
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

        <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-base font-extrabold text-slate-900">RHU Medical Doctors</h3>
                <p class="mt-1 text-xs font-medium text-slate-500">Select a doctor to adjust their weekly recurring schedule or manage holiday/leave overrides.</p>
            </div>

            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Weekly Schedule</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Upcoming Exceptions</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($doctors as $doctor)
                            <tr class="card-hover">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <div class="flex items-center gap-3">
                                        <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-xs font-extrabold text-green-700">
                                            {{ collect(explode(' ', trim($doctor->name)))->take(2)->map(fn ($p) => mb_substr($p, 0, 1))->implode('') }}
                                        </div>
                                        <div>
                                            <p class="text-sm font-bold text-slate-900">{{ $doctor->name }}</p>
                                            <p class="text-xs font-medium text-slate-500">{{ $doctor->email }}</p>
                                        </div>
                                    </div>
                                </td>
                                <td class="px-5 py-4">
                                    <span class="text-sm font-semibold text-slate-700">{{ $doctor->schedule_summary }}</span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    @if ($doctor->active_exceptions_count > 0)
                                        <span class="inline-flex items-center rounded-full bg-amber-100 px-2.5 py-0.5 text-xs font-extrabold text-amber-800">
                                            {{ $doctor->active_exceptions_count }} scheduled
                                        </span>
                                    @else
                                        <span class="inline-flex items-center rounded-full bg-slate-100 px-2.5 py-0.5 text-xs font-semibold text-slate-500">
                                            None
                                        </span>
                                    @endif
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <div class="flex items-center justify-end gap-2">
                                        <a
                                            href="{{ route('doctor-availability.edit', $doctor) }}"
                                            class="inline-flex items-center rounded-lg bg-green-50 px-3 py-1.5 text-xs font-bold text-green-700 transition hover:bg-green-100"
                                        >
                                            Manage Schedule
                                        </a>
                                        <a
                                            href="{{ route('doctor-availability.exceptions', $doctor) }}"
                                            class="inline-flex items-center rounded-lg bg-slate-100 px-3 py-1.5 text-xs font-bold text-slate-700 transition hover:bg-slate-200"
                                        >
                                            Exceptions
                                        </a>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-5 py-10 text-center text-sm font-medium text-slate-500">
                                    No active doctors found in the system.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            @if ($doctors->hasPages())
                <div class="border-t border-slate-100 px-5 py-4">
                    {{ $doctors->links() }}
                </div>
            @endif
        </div>
    </div>
@endsection
