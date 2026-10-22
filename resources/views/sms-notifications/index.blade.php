@php
    $statusClasses = [
        'Pending' => 'bg-amber-100 text-amber-700',
        'Sent' => 'bg-green-100 text-green-700',
        'Failed' => 'bg-red-100 text-red-700',
    ];
@endphp

@extends('layouts.dashboard', [
    'pageTitle' => 'SMS Notifications',
    'pageSubtitle' => 'Simulated outbound SMS log.',
    'context' => 'A read-only log of booking confirmations and status-change notices simulated for each patient. No real gateway is connected yet.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'SMS Notifications'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <form method="GET" action="{{ route('sms-notifications.index') }}" class="grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_12rem_auto]">
            <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search patient name or contact number"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ $status }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700 focus:outline-none focus:ring-2 focus:ring-slate-500 focus:ring-offset-2">Filter</button>
        </form>

        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Patient</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Message</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Recipient Number</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Sent At</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($notifications as $notification)
                            <tr class="transition-colors duration-150 hover:bg-slate-50" x-data="{ expanded: false }">
                                <td class="whitespace-nowrap px-5 py-4">
                                    <p class="text-sm font-bold text-slate-900">{{ $notification->patient->full_name }}</p>
                                    <p class="text-xs font-medium text-slate-500">{{ $notification->patient->contact_number }}</p>
                                </td>
                                <td class="px-5 py-4 text-sm text-slate-600">
                                    <p x-show="!expanded" class="max-w-sm truncate">{{ $notification->message }}</p>
                                    <p x-show="expanded" x-cloak class="max-w-sm whitespace-pre-wrap">{{ $notification->message }}</p>
                                    <button
                                        type="button"
                                        class="mt-1 rounded text-xs font-bold text-green-700 transition hover:text-green-900 focus:outline-none focus:ring-2 focus:ring-green-500"
                                        x-on:click="expanded = ! expanded"
                                        x-text="expanded ? 'Show less' : 'View full'"
                                    ></button>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $notification->recipient_number }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $statusClasses[$notification->delivery_status] ?? 'bg-slate-100 text-slate-600' }}">
                                        {{ $notification->delivery_status }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">
                                    {{ $notification->sent_at?->format('M d, Y h:i A') ?? 'Not sent' }}
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5">
                                    <x-empty-state message="No SMS notifications logged yet." />
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $notifications->links() }}
            </div>
        </div>
    </div>
@endsection
