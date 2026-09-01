@php
    $priorityClasses = [
        'Regular' => 'bg-slate-100 text-slate-600',
        'Senior Citizen' => 'bg-purple-100 text-purple-700',
        'PWD' => 'bg-purple-100 text-purple-700',
        'Pregnant' => 'bg-purple-100 text-purple-700',
    ];

    $statusClasses = [
        'Waiting' => 'bg-amber-100 text-amber-700',
        'Serving' => 'bg-blue-100 text-blue-700',
        'Completed' => 'bg-green-100 text-green-700',
        'Skipped' => 'bg-slate-100 text-slate-600',
        'Cancelled' => 'bg-slate-100 text-slate-600',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'Smart Queue',
    'pageSubtitle' => 'Manage today\'s walk-through-clinic patient flow.',
    'context' => 'Call the next patient and track who is waiting, being served, or already seen today.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Smart Queue'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div
        class="space-y-6"
        x-data="{
            waiting: @js($waiting),
            serving: @js($serving),
            history: @js($history),
            refreshing: false,
            statusUrlTemplate: '{{ route('queue.status', ['queue' => '__ID__']) }}',
            statusUrl(id) {
                return this.statusUrlTemplate.replace('__ID__', id);
            },
            init() {
                setInterval(() => this.refresh(), 10000);
            },
            async refresh() {
                this.refreshing = true;

                try {
                    const response = await fetch('{{ route('queue.board-data') }}', {
                        headers: {
                            'Accept': 'application/json',
                            'X-Background-Poll': 'true',
                        },
                    });

                    if (! response.ok || ! response.headers.get('content-type')?.includes('application/json')) {
                        return;
                    }

                    const data = await response.json();
                    this.waiting = data.waiting ?? [];
                    this.serving = data.serving ?? [];
                    this.history = data.history ?? [];
                } finally {
                    this.refreshing = false;
                }
            },
        }"
    >
        @if ($canManageQueue)
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-end">
                <form method="POST" action="{{ route('queue.call-next') }}">
                    @csrf
                    <button type="submit" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800 focus:outline-none focus:ring-2 focus:ring-green-500 focus:ring-offset-2">
                        Call Next Patient
                    </button>
                </form>
            </div>
        @endif

        @if (session('status'))
            <script>
                window.addEventListener('DOMContentLoaded', () => {
                    window.dispatchEvent(new CustomEvent('toast', {
                        detail: { message: @json(session('status')), type: 'success' },
                    }));
                });
            </script>
        @endif

        <div class="grid gap-6 xl:grid-cols-2">
            <section class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-extrabold text-slate-900">Waiting</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Queue No.</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Priority</th>
                                @if ($canManageQueue)
                                    <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                                @endif
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="refreshing && waiting.length === 0">
                                <x-skeleton-table-row :cols="$canManageQueue ? 4 : 3" />
                            </template>
                            <template x-for="entry in waiting" :key="entry.id">
                                <tr class="transition-colors duration-150 hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-extrabold text-slate-800" x-text="entry.queue_number"></td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="text-sm font-bold text-slate-900" x-text="entry.patient.name"></p>
                                        <p class="text-xs font-medium text-slate-500" x-text="entry.patient.contact"></p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="entry.priority_type"></td>
                                    @if ($canManageQueue)
                                        <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                            <form method="POST" :action="statusUrl(entry.id)" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="Skipped">
                                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-slate-500 transition hover:bg-slate-100 focus:outline-none focus:ring-2 focus:ring-slate-400">Skip</button>
                                            </form>
                                            <form method="POST" :action="statusUrl(entry.id)" class="inline">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="status" value="Cancelled">
                                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-red-600 transition hover:bg-red-50 focus:outline-none focus:ring-2 focus:ring-red-400">Cancel</button>
                                            </form>
                                        </td>
                                    @endif
                                </tr>
                            </template>
                            <template x-if="!refreshing && waiting.length === 0">
                                <tr>
                                    <td colspan="{{ $canManageQueue ? 4 : 3 }}">
                                        <x-empty-state message="No one is waiting." />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>

            <section class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
                <div class="border-b border-slate-100 px-5 py-4">
                    <h3 class="text-lg font-extrabold text-slate-900">Currently Serving</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-slate-100">
                        <thead class="bg-slate-50">
                            <tr>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Queue No.</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Doctor</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            <template x-if="refreshing && serving.length === 0">
                                <x-skeleton-table-row :cols="3" />
                            </template>
                            <template x-for="entry in serving" :key="entry.id">
                                <tr class="transition-colors duration-150 hover:bg-slate-50">
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-extrabold text-slate-800" x-text="entry.queue_number"></td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <p class="text-sm font-bold text-slate-900" x-text="entry.patient.name"></p>
                                        <p class="text-xs font-medium text-slate-500" x-text="entry.patient.contact"></p>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="entry.doctor"></td>
                                </tr>
                            </template>
                            <template x-if="!refreshing && serving.length === 0">
                                <tr>
                                    <td colspan="3">
                                        <x-empty-state message="No one is being served." />
                                    </td>
                                </tr>
                            </template>
                        </tbody>
                    </table>
                </div>
            </section>
        </div>

        <section class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="border-b border-slate-100 px-5 py-4">
                <h3 class="text-lg font-extrabold text-slate-900">Today's History</h3>
            </div>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Queue No.</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Priority</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        <template x-if="refreshing && history.length === 0">
                            <x-skeleton-table-row :cols="4" />
                        </template>
                        <template x-for="entry in history" :key="entry.id">
                            <tr class="transition-colors duration-150 hover:bg-slate-50">
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-extrabold text-slate-800" x-text="entry.queue_number"></td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900" x-text="entry.patient.name"></p>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-semibold text-slate-600" x-text="entry.priority_type"></td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold"
                                        :class="{
                                            'bg-green-100 text-green-700': entry.queue_status === 'Completed',
                                            'bg-slate-100 text-slate-600': entry.queue_status === 'Skipped' || entry.queue_status === 'Cancelled',
                                        }"
                                        x-text="entry.queue_status"
                                    ></span>
                                </td>
                            </tr>
                        </template>
                        <template x-if="!refreshing && history.length === 0">
                            <tr>
                                <td colspan="4">
                                    <x-empty-state message="No queue activity yet today." />
                                </td>
                            </tr>
                        </template>
                    </tbody>
                </table>
            </div>
        </section>
    </div>
@endsection
