@php
    $roleLabel = auth()->user()->role?->name ?? 'Staff';
    $navGroups = match ($roleLabel) {
        'Administrator' => [
            'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
            'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
        ],
        'Doctor' => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'My Appointments', 'My Availability', 'Patient Visit History', 'Profile']],
        'Nurse' => ['MAIN MENU' => ['Dashboard', 'Patient Queue', 'Patient Records', 'Vitals/Triage', 'Profile']],
        'Midwife' => ['MAIN MENU' => ['Dashboard', 'Maternal Care Appointments', 'Patient Records', 'Visit History', 'Profile']],
        'Data Encoder' => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Appointment Entry', 'SMS Notifications Log', 'Profile']],
        default => ['MAIN MENU' => ['Dashboard', 'Patient Records', 'Profile']],
    };
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Patient Records',
    'pageSubtitle' => 'Manage RHUConnect patient information.',
    'context' => 'Search, review, and maintain patient demographic and clinical intake records.',
    'portalLabel' => $roleLabel === 'Administrator' ? 'Admin Portal' : $roleLabel.' Portal',
    'roleLabel' => $roleLabel,
    'navGroups' => $navGroups,
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div class="flex gap-2">
                @if ($canArchive)
                    <a href="{{ route('patients.index') }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ ! $showArchived ? 'bg-green-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        Active
                    </a>
                    <a href="{{ route('patients.index', ['archived' => 1]) }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $showArchived ? 'bg-green-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                        Archived
                    </a>
                @endif
            </div>
            <a href="{{ route('patients.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                Add Patient
            </a>
        </div>

        @if (session('status'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: @json(session('status')), type: 'success' },
                    }));
                });
            </script>
        @endif

        <form method="GET" action="{{ route('patients.index') }}" class="card-hover grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_12rem_auto]">
            @if ($showArchived)
                <input type="hidden" name="archived" value="1">
            @endif

            <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or contact number"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <select name="sex" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All sex</option>
                @foreach ($sexes as $sex)
                    <option value="{{ $sex }}" @selected(($filters['sex'] ?? '') === $sex || ($filters['sex'] ?? '') === strtoupper($sex))>{{ $sex }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Filter</button>
        </form>

        <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Date of Birth</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Sex</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Contact</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($patients as $patient)
                            <tr class="card-hover">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $patient->full_name }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ $patient->address }}</p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $patient->birthdate?->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $patient->sex === 'Female' ? 'bg-purple-100 text-purple-700' : 'bg-blue-100 text-blue-700' }}">
                                        {{ $patient->sex }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $patient->contact_number }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $patient->trashed() ? 'bg-slate-100 text-slate-600' : 'bg-green-100 text-green-700' }}">
                                        {{ $patient->trashed() ? 'Archived' : 'Active' }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('patients.show', $patient) }}" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900">View</a>
                                        @unless ($patient->trashed())
                                            <a href="{{ route('patients.edit', $patient) }}" class="rounded-lg px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">Edit</a>
                                            @if ($canArchive)
                                                <form method="POST" action="{{ route('patients.archive', $patient) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">Archive</button>
                                                </form>
                                            @endif
                                        @else
                                            @if ($canArchive)
                                                <form method="POST" action="{{ route('patients.restore', $patient->id) }}">
                                                    @csrf
                                                    @method('PATCH')
                                                    <button type="submit" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900">Restore</button>
                                                </form>
                                            @endif
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm font-medium text-slate-500">No patient records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $patients->links() }}
            </div>
        </div>
    </div>
@endsection
