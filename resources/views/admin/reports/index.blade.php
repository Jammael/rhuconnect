@php
    $rangeLink = fn (string $preset) => route('admin.reports.index', ['range' => $preset]);
    $avgUtilization = collect($doctorUtilization['doctors'])->avg('utilization_percent');

    $statusColors = [
        'Pending' => '#f59e0b',
        'Approved' => '#15803d',
        'Completed' => '#2563eb',
        'Cancelled' => '#94a3b8',
        'No Show' => '#dc2626',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Reports & Analytics',
    'pageSubtitle' => 'Clinic-wide operational reporting.',
    'context' => 'Appointment volume, doctor utilization, patient demographics, and queue turnaround for the selected period.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Reports & Analytics'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <div class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
            <div class="flex flex-wrap gap-2">
                <a href="{{ $rangeLink('today') }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $range['range'] === 'today' ? 'bg-green-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    Today
                </a>
                <a href="{{ $rangeLink('week') }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $range['range'] === 'week' ? 'bg-green-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    This Week
                </a>
                <a href="{{ $rangeLink('month') }}" class="rounded-lg px-4 py-2 text-sm font-semibold transition {{ $range['range'] === 'month' ? 'bg-green-700 text-white' : 'bg-white text-slate-600 hover:bg-slate-50' }}">
                    This Month
                </a>
            </div>

            <form method="GET" action="{{ route('admin.reports.index') }}" class="grid gap-4 border-t border-slate-100 pt-4 md:grid-cols-[auto_auto_auto_auto]">
                <input type="hidden" name="range" value="custom">
                <input name="from" type="date" value="{{ $range['range'] === 'custom' ? $range['start']->toDateString() : '' }}"
                    class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <input name="to" type="date" value="{{ $range['range'] === 'custom' ? $range['end']->toDateString() : '' }}"
                    class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">
                    Custom Range
                </button>
                <p class="self-center text-sm font-medium text-slate-500">
                    Showing {{ $range['start']->format('M d, Y') }} &ndash; {{ $range['end']->format('M d, Y') }}
                </p>
            </form>
        </div>

        {{-- KPI summary strip --}}
        <div class="grid gap-6 sm:grid-cols-2 xl:grid-cols-4">
            <article class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <p class="text-sm font-bold text-slate-500">Total Appointments</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $appointmentVolume['totals']['total'] }}</p>
                <p class="mt-2 text-sm font-medium text-slate-500">In selected range</p>
            </article>
            <article class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <p class="text-sm font-bold text-slate-500">No-Show Rate</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $appointmentVolume['no_show_rate'] }}%</p>
                <p class="mt-2 text-sm font-medium text-slate-500">Of resolved appointments</p>
            </article>
            <article class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <p class="text-sm font-bold text-slate-500">Avg Doctor Utilization</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $avgUtilization !== null ? round($avgUtilization, 1) : 0 }}%</p>
                <p class="mt-2 text-sm font-medium text-slate-500">Across active doctors</p>
            </article>
            <article class="card-hover rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60">
                <p class="text-sm font-bold text-slate-500">Avg Queue Turnaround</p>
                <p class="mt-3 text-3xl font-extrabold text-slate-900">{{ $queueTurnaround['avg_minutes'] ?? 0 }} <span class="text-lg font-bold text-slate-400">min</span></p>
                <p class="mt-2 text-sm font-medium text-slate-500">Check-in to completion</p>
            </article>
        </div>

        {{-- Appointment Volume --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="flex flex-col gap-2 border-b border-slate-100 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h3 class="text-lg font-extrabold text-slate-900">Appointment Volume</h3>
                    <p class="text-sm font-medium text-slate-500">No-show rate: {{ $appointmentVolume['no_show_rate'] }}%</p>
                </div>
                <a href="{{ route('admin.reports.export.appointments', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Export CSV
                </a>
            </div>

            <div class="p-5">
                <div id="chart-appointment-volume"></div>
            </div>

            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Date</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Pending</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Approved</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Completed</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Cancelled</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">No Show</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($appointmentVolume['days'] as $day)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $day['Pending'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $day['Approved'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $day['Completed'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $day['Cancelled'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $day['No Show'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-900">{{ $day['total'] }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7">
                                    <x-empty-state message="No appointments in this range." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-4 sm:hidden">
                @forelse ($appointmentVolume['days'] as $day)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-extrabold text-slate-900">{{ \Illuminate\Support\Carbon::parse($day['date'])->format('M d, Y') }}</p>
                            <p class="text-sm font-bold text-slate-900">{{ $day['total'] }} total</p>
                        </div>
                        <dl class="mt-3 grid grid-cols-2 gap-x-4 gap-y-2 text-sm">
                            <div class="flex justify-between"><dt class="font-medium text-slate-500">Pending</dt><dd class="font-semibold text-slate-700">{{ $day['Pending'] }}</dd></div>
                            <div class="flex justify-between"><dt class="font-medium text-slate-500">Approved</dt><dd class="font-semibold text-slate-700">{{ $day['Approved'] }}</dd></div>
                            <div class="flex justify-between"><dt class="font-medium text-slate-500">Completed</dt><dd class="font-semibold text-slate-700">{{ $day['Completed'] }}</dd></div>
                            <div class="flex justify-between"><dt class="font-medium text-slate-500">Cancelled</dt><dd class="font-semibold text-slate-700">{{ $day['Cancelled'] }}</dd></div>
                            <div class="flex justify-between"><dt class="font-medium text-slate-500">No Show</dt><dd class="font-semibold text-slate-700">{{ $day['No Show'] }}</dd></div>
                        </dl>
                    </div>
                @empty
                    <p class="px-1 py-6 text-center text-sm font-medium text-slate-500">No appointments in this range.</p>
                @endforelse
            </div>
        </section>

        {{-- Doctor Utilization --}}
        <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Doctor Utilization</h3>
                <a href="{{ route('admin.reports.export.doctors', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                    Export CSV
                </a>
            </div>

            @if (count($doctorUtilization['doctors']) > 0)
                <div class="p-5">
                    <div id="chart-doctor-utilization"></div>
                </div>
            @endif

            <div class="hidden overflow-x-auto sm:block">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Booked</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">No Show</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Capacity</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Utilization</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($doctorUtilization['doctors'] as $row)
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-900">{{ $row['doctor'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $row['booked'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $row['no_show'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm text-slate-600">{{ $row['capacity'] }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-700">{{ $row['utilization_percent'] }}%</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state message="No active doctors found." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="grid gap-3 p-4 sm:hidden">
                @forelse ($doctorUtilization['doctors'] as $row)
                    <div class="rounded-xl border border-slate-100 bg-slate-50/70 p-4">
                        <div class="flex items-center justify-between gap-4">
                            <p class="text-sm font-extrabold text-slate-900">{{ $row['doctor'] }}</p>
                            <p class="text-sm font-semibold text-slate-700">{{ $row['utilization_percent'] }}%</p>
                        </div>
                        <dl class="mt-3 grid grid-cols-3 gap-x-4 gap-y-2 text-sm">
                            <div class="flex flex-col"><dt class="text-xs font-medium text-slate-500">Booked</dt><dd class="font-semibold text-slate-700">{{ $row['booked'] }}</dd></div>
                            <div class="flex flex-col"><dt class="text-xs font-medium text-slate-500">No Show</dt><dd class="font-semibold text-slate-700">{{ $row['no_show'] }}</dd></div>
                            <div class="flex flex-col"><dt class="text-xs font-medium text-slate-500">Capacity</dt><dd class="font-semibold text-slate-700">{{ $row['capacity'] }}</dd></div>
                        </dl>
                    </div>
                @empty
                    <p class="px-1 py-6 text-center text-sm font-medium text-slate-500">No active doctors found.</p>
                @endforelse
            </div>
        </section>

        <div class="grid gap-6 xl:grid-cols-2">
            {{-- Patient Demographics --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-extrabold text-slate-900">Patient Demographics</h3>
                    <a href="{{ route('admin.reports.export.demographics', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Export CSV
                    </a>
                </div>
                <div class="grid gap-6 p-5">
                    <p class="text-sm font-medium text-slate-500">{{ $patientDemographics['total_patients'] }} patients seen in this range.</p>

                    @if ($patientDemographics['total_patients'] > 0)
                        <div class="grid gap-6 sm:grid-cols-2">
                            <div>
                                <h4 class="text-sm font-extrabold uppercase tracking-wide text-slate-400">Sex</h4>
                                <div id="chart-demographics-sex" class="mt-2"></div>
                            </div>
                            <div>
                                <h4 class="text-sm font-extrabold uppercase tracking-wide text-slate-400">Age Bracket</h4>
                                <div id="chart-demographics-age" class="mt-2"></div>
                            </div>
                        </div>
                    @endif

                    <div>
                        <h4 class="text-sm font-extrabold uppercase tracking-wide text-slate-400">Barangay</h4>
                        <ul class="mt-2 space-y-1 text-sm text-slate-700">
                            @forelse ($patientDemographics['barangays'] as $label => $count)
                                <li class="flex justify-between"><span>{{ $label }}</span><span class="font-semibold">{{ $count }}</span></li>
                            @empty
                                <li class="text-slate-500">No data.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </section>

            {{-- Queue Turnaround --}}
            <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                <div class="flex items-center justify-between border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-extrabold text-slate-900">Queue Turnaround</h3>
                    <a href="{{ route('admin.reports.export.queue', request()->query()) }}" class="inline-flex items-center justify-center rounded-lg border border-slate-300 bg-white px-4 py-2 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                        Export CSV
                    </a>
                </div>
                <div class="grid gap-6 p-5">
                    @if ($queueTurnaround['count'] > 0)
                        <dl class="grid grid-cols-2 gap-4 sm:grid-cols-4">
                            <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Completed</dt><dd class="mt-1 text-lg font-bold text-slate-900">{{ $queueTurnaround['count'] }}</dd></div>
                            <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Avg (min)</dt><dd class="mt-1 text-lg font-bold text-slate-900">{{ $queueTurnaround['avg_minutes'] }}</dd></div>
                            <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Median (min)</dt><dd class="mt-1 text-lg font-bold text-slate-900">{{ $queueTurnaround['median_minutes'] }}</dd></div>
                            <div><dt class="text-xs font-extrabold uppercase tracking-wide text-slate-400">Min / Max</dt><dd class="mt-1 text-lg font-bold text-slate-900">{{ $queueTurnaround['min_minutes'] }} / {{ $queueTurnaround['max_minutes'] }}</dd></div>
                        </dl>

                        <div>
                            <h4 class="text-sm font-extrabold uppercase tracking-wide text-slate-400">By Priority Type</h4>
                            <div id="chart-queue-priority" class="mt-2"></div>
                        </div>
                    @else
                        <p class="text-sm font-medium text-slate-500">No completed queue entries in this range.</p>
                    @endif
                </div>
            </section>
        </div>
    </div>

    <script>
        window.addEventListener('DOMContentLoaded', () => {
            const baseOptions = {
                chart: { toolbar: { show: false }, fontFamily: 'Figtree, sans-serif' },
                dataLabels: { enabled: false },
                legend: { fontFamily: 'Figtree, sans-serif' },
            };

            // Appointment Volume — stacked bar per day, one series per status
            const volumeEl = document.querySelector('#chart-appointment-volume');
            if (volumeEl) {
                const days = @json($appointmentVolume['days']);
                const statuses = @json(array_keys($statusColors));
                const colors = @json(array_values($statusColors));

                new ApexCharts(volumeEl, {
                    ...baseOptions,
                    chart: { ...baseOptions.chart, type: 'bar', height: 320, stacked: true },
                    series: statuses.map((status) => ({
                        name: status,
                        data: days.map((day) => day[status] ?? 0),
                    })),
                    colors,
                    xaxis: {
                        categories: days.map((day) => new Date(day.date + 'T00:00:00').toLocaleDateString('en-US', { month: 'short', day: 'numeric' })),
                        labels: { style: { fontFamily: 'Figtree, sans-serif' } },
                    },
                    plotOptions: { bar: { columnWidth: '60%', borderRadius: 3 } },
                }).render();
            }

            // Doctor Utilization — horizontal bar
            const utilizationEl = document.querySelector('#chart-doctor-utilization');
            if (utilizationEl) {
                const doctors = @json($doctorUtilization['doctors']);

                new ApexCharts(utilizationEl, {
                    ...baseOptions,
                    chart: { ...baseOptions.chart, type: 'bar', height: Math.max(160, doctors.length * 60) },
                    series: [{ name: 'Utilization %', data: doctors.map((d) => d.utilization_percent) }],
                    colors: ['#15803d'],
                    xaxis: {
                        categories: doctors.map((d) => d.doctor),
                        max: 100,
                        labels: { formatter: (val) => val + '%', style: { fontFamily: 'Figtree, sans-serif' } },
                    },
                    plotOptions: { bar: { horizontal: true, borderRadius: 3, barHeight: '55%' } },
                }).render();
            }

            // Patient Demographics — sex donut
            const sexEl = document.querySelector('#chart-demographics-sex');
            if (sexEl) {
                const sex = @json($patientDemographics['sex']);

                new ApexCharts(sexEl, {
                    ...baseOptions,
                    chart: { ...baseOptions.chart, type: 'donut', height: 220 },
                    series: Object.values(sex),
                    labels: Object.keys(sex),
                    colors: ['#2563eb', '#db2777'],
                    legend: { ...baseOptions.legend, position: 'bottom' },
                }).render();
            }

            // Patient Demographics — age bracket bar
            const ageEl = document.querySelector('#chart-demographics-age');
            if (ageEl) {
                const ageBrackets = @json($patientDemographics['age_brackets']);

                new ApexCharts(ageEl, {
                    ...baseOptions,
                    chart: { ...baseOptions.chart, type: 'bar', height: 220 },
                    series: [{ name: 'Patients', data: Object.values(ageBrackets) }],
                    colors: ['#9333ea'],
                    xaxis: {
                        categories: Object.keys(ageBrackets),
                        labels: { style: { fontFamily: 'Figtree, sans-serif' } },
                    },
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 3 } },
                }).render();
            }

            // Queue Turnaround — avg minutes by priority type
            const queueEl = document.querySelector('#chart-queue-priority');
            if (queueEl) {
                const byPriority = @json($queueTurnaround['by_priority'] ?? []);
                const types = Object.keys(byPriority);

                new ApexCharts(queueEl, {
                    ...baseOptions,
                    chart: { ...baseOptions.chart, type: 'bar', height: 220 },
                    series: [{ name: 'Avg minutes', data: types.map((type) => byPriority[type].avg_minutes ?? 0) }],
                    colors: ['#f59e0b'],
                    xaxis: {
                        categories: types,
                        labels: { style: { fontFamily: 'Figtree, sans-serif' } },
                    },
                    plotOptions: { bar: { columnWidth: '50%', borderRadius: 3 } },
                }).render();
            }
        });
    </script>
@endsection
