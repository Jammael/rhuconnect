@php
    $statusClasses = [
        'Pending' => 'bg-amber-100 text-amber-700',
        'Approved' => 'bg-green-100 text-green-700',
        'Completed' => 'bg-blue-100 text-blue-700',
        'Cancelled' => 'bg-slate-100 text-slate-600',
        'No Show' => 'bg-red-100 text-red-700',
    ];

    $isTerminal = in_array($appointment->status, ['Cancelled', 'Completed', 'No Show'], true);
    $value = fn ($text) => filled($text) ? $text : 'Not recorded';
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Appointment Details',
    'pageSubtitle' => 'Manage RHUConnect appointment bookings.',
    'context' => 'Review schedule, visit, and status details for this appointment.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Online Appointments', 'link' => route('appointments.index')],
        ['label' => 'Appointment Details'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-end">
            <a href="{{ route('appointments.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Appointments
            </a>
            @if ($canManage && ! $isTerminal)
                <a href="{{ route('appointments.edit', $appointment) }}" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                    Edit Appointment
                </a>
            @endif
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

        <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-4 border-b border-slate-100 pb-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p class="text-sm font-semibold uppercase tracking-wide text-green-700">Appointment</p>
                    <h2 class="mt-2 text-2xl font-extrabold text-slate-900">{{ $appointment->patient->full_name }}</h2>
                    <p class="mt-1 text-sm font-medium text-slate-500">Record #{{ str_pad((string) $appointment->id, 5, '0', STR_PAD_LEFT) }}</p>
                </div>
                <span class="inline-flex w-fit rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClasses[$appointment->status] ?? 'bg-slate-100 text-slate-600' }}">
                    {{ $appointment->status }}
                </span>
            </div>

            <div class="mt-6 grid gap-8">
                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Schedule</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-3">
                        <div><dt class="text-sm font-semibold text-slate-500">Doctor</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->doctor->name }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Date</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->appointment_date->format('M d, Y') }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Time</dt><dd class="mt-1 text-base text-slate-900">{{ \Illuminate\Support\Carbon::parse($appointment->appointment_time)->format('h:i A') }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Patient</h3>
                    <dl class="mt-4 grid gap-6 md:grid-cols-2">
                        <div><dt class="text-sm font-semibold text-slate-500">Name</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->patient->full_name }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Contact Number</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->patient->contact_number }}</dd></div>
                    </dl>
                </section>

                <section>
                    <h3 class="text-lg font-extrabold text-slate-900">Visit Details</h3>
                    <dl class="mt-4 grid gap-6">
                        <div><dt class="text-sm font-semibold text-slate-500">Purpose of Visit</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->purpose_of_visit }}</dd></div>
                        <div><dt class="text-sm font-semibold text-slate-500">Remarks</dt><dd class="mt-1 text-base text-slate-900">{{ $value($appointment->remarks) }}</dd></div>
                    </dl>
                </section>
            </div>
        </div>

        @if ($appointment->vitals && $canViewVitals)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Vitals</h3>
                <dl class="mt-4 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
                    <div><dt class="text-sm font-semibold text-slate-500">Blood Pressure</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->blood_pressure_systolic }}/{{ $appointment->vitals->blood_pressure_diastolic }} mmHg</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Temperature</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->temperature_celsius }} &deg;C</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Pulse Rate</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->pulse_rate }} bpm</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Respiratory Rate</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->respiratory_rate }} breaths/min</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Oxygen Saturation</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->oxygen_saturation }}%</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Weight</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->weight_kg }} kg</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Height</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->height_cm }} cm</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Recorded By</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->recordedBy->name }}</dd></div>
                </dl>
                @if ($appointment->vitals->chief_complaint)
                    <div class="mt-6">
                        <dt class="text-sm font-semibold text-slate-500">Chief Complaint</dt>
                        <dd class="mt-1 text-base text-slate-900">{{ $appointment->vitals->chief_complaint }}</dd>
                    </div>
                @endif
                <p class="mt-4 text-xs font-medium text-slate-400">Recorded {{ $appointment->vitals->created_at->format('M d, Y h:i A') }}</p>
            </div>
        @elseif ($canRecordVitals)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Record Vitals</h3>
                <p class="mt-1 text-sm text-slate-500">Capture intake measurements before the consultation.</p>
                @include('vitals._form')
            </div>
        @endif

        @if ($appointment->visitHistory)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Visit Record</h3>
                <dl class="mt-4 grid gap-6">
                    <div><dt class="text-sm font-semibold text-slate-500">Diagnosis</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->visitHistory->diagnosis }}</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Prescription</dt><dd class="mt-1 text-base text-slate-900">{{ $value($appointment->visitHistory->prescription) }}</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Notes</dt><dd class="mt-1 text-base text-slate-900">{{ $value($appointment->visitHistory->notes) }}</dd></div>
                </dl>
            </div>
        @endif

        @if ($appointment->queue)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Queue Status</h3>
                <dl class="mt-4 grid gap-6 md:grid-cols-3">
                    <div><dt class="text-sm font-semibold text-slate-500">Queue Number</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->queue->queue_number }}</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Priority Type</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->queue->priority_type }}</dd></div>
                    <div><dt class="text-sm font-semibold text-slate-500">Status</dt><dd class="mt-1 text-base text-slate-900">{{ $appointment->queue->queue_status }}</dd></div>
                </dl>
            </div>
        @elseif ($canCheckIn)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Check In</h3>
                <p class="mt-1 text-sm text-slate-500">Add this patient to today's Smart Queue.</p>

                @error('appointment')
                    <p class="mt-3 text-sm font-semibold text-red-600">{{ $message }}</p>
                @enderror

                <form method="POST" action="{{ route('queue.check-in', $appointment) }}" class="mt-4 flex flex-col gap-3 sm:flex-row sm:items-end">
                    @csrf
                    <div class="flex-1">
                        <label class="text-sm font-semibold text-slate-500">Priority Type</label>
                        <select name="priority_type" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                            @foreach (['Regular', 'Senior Citizen', 'PWD', 'Pregnant'] as $priorityType)
                                <option value="{{ $priorityType }}" @selected($suggestedPriorityType === $priorityType)>{{ $priorityType }}</option>
                            @endforeach
                        </select>
                    </div>
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Check In
                    </button>
                </form>
            </div>
        @endif

        @if (! $isTerminal && ($canManage || $canComplete))
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Update Status</h3>
                <p class="mt-1 text-sm text-slate-500">Move this appointment to its next stage in the lifecycle.</p>

                <div class="mt-4 flex flex-wrap gap-3">
                    @if ($canManage && $appointment->status === 'Pending')
                        <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="Approved">
                            <button type="submit" class="inline-flex justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                                Approve
                            </button>
                        </form>
                    @endif

                    @if ($canManage)
                        <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="Cancelled">
                            <button type="submit" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 focus:outline-none focus:ring-2 focus:ring-slate-400 focus:ring-offset-2">
                                Cancel Appointment
                            </button>
                        </form>
                    @endif

                    @if ($canComplete)
                        <form method="POST" action="{{ route('appointments.status', $appointment) }}">
                            @csrf
                            @method('PATCH')
                            <input type="hidden" name="status" value="No Show">
                            <button type="submit" class="inline-flex justify-center rounded-lg bg-red-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-red-800 focus:outline-none focus:ring-2 focus:ring-red-500 focus:ring-offset-2">
                                Mark No Show
                            </button>
                        </form>
                    @endif
                </div>
            </div>
        @endif

        @if ($canComplete && ! $isTerminal)
            <div class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
                <h3 class="text-lg font-extrabold text-slate-900">Complete Visit</h3>
                <p class="mt-1 text-sm text-slate-500">Document the diagnosis to mark this appointment Completed.</p>

                <form method="POST" action="{{ route('appointments.status', $appointment) }}" class="mt-4 space-y-4">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="Completed">

                    <div>
                        <label class="text-sm font-semibold text-slate-500">Diagnosis</label>
                        <textarea name="diagnosis" rows="2" required class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">{{ old('diagnosis') }}</textarea>
                        @error('diagnosis')
                            <p class="mt-1 text-sm font-semibold text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-500">Prescription (optional)</label>
                        <textarea name="prescription" rows="2" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">{{ old('prescription') }}</textarea>
                    </div>

                    <div>
                        <label class="text-sm font-semibold text-slate-500">Notes (optional)</label>
                        <textarea name="notes" rows="2" class="mt-1 w-full rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">{{ old('notes') }}</textarea>
                    </div>

                    <button type="submit" class="inline-flex justify-center rounded-lg bg-blue-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-blue-800 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        Mark Completed &amp; Save Visit Record
                    </button>
                </form>
            </div>
        @endif
    </div>
@endsection
