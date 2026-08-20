@extends('layouts.dashboard', [
    'pageTitle' => 'User Details',
    'pageSubtitle' => 'Review staff account information.',
    'context' => 'Check account identity, assigned role, status, and audit-friendly timestamps.',
    'portalLabel' => 'Admin Portal',
    'roleLabel' => 'Administrator',
    'navGroups' => [
        'MAIN MENU' => ['Dashboard', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
        'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex justify-end">
            <a href="{{ route('admin.users.edit', $managedUser) }}" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                Edit User
            </a>
        </div>

        @if (session('status'))
            <div class="rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm font-medium text-green-800">
                {{ session('status') }}
            </div>
        @endif

        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            <dl class="grid gap-6 md:grid-cols-2">
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Name</dt>
                    <dd class="mt-1 text-base font-semibold text-slate-900">{{ $managedUser->name }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Email</dt>
                    <dd class="mt-1 text-base text-slate-900">{{ $managedUser->email }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Role</dt>
                    <dd class="mt-1 text-base text-slate-900">{{ $managedUser->role?->name ?? 'Unassigned' }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Account Status</dt>
                    <dd class="mt-1">
                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $managedUser->account_status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                            {{ ucfirst(strtolower($managedUser->account_status)) }}
                        </span>
                    </dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Created Date</dt>
                    <dd class="mt-1 text-base text-slate-900">{{ $managedUser->created_at?->format('M d, Y h:i A') }}</dd>
                </div>
                <div>
                    <dt class="text-sm font-semibold text-slate-500">Last Updated</dt>
                    <dd class="mt-1 text-base text-slate-900">{{ $managedUser->updated_at?->format('M d, Y h:i A') }}</dd>
                </div>
            </dl>
        </div>

        <div class="flex flex-col-reverse gap-3 sm:flex-row sm:justify-between">
            <a href="{{ route('admin.users.index') }}" class="inline-flex justify-center rounded-lg border border-slate-300 bg-white px-5 py-2.5 text-sm font-semibold text-slate-700 transition hover:bg-slate-50">
                Back to Users
            </a>
            @unless ($managedUser->is(auth()->user()))
                <form method="POST" action="{{ route('admin.users.status', $managedUser) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="account_status" value="{{ $managedUser->account_status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
                    <button type="submit" class="inline-flex justify-center rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">
                        {{ $managedUser->account_status === 'ACTIVE' ? 'Deactivate Account' : 'Activate Account' }}
                    </button>
                </form>
            @endunless
        </div>
    </div>
@endsection
