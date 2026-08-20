@extends('layouts.dashboard', [
    'pageTitle' => 'User Management',
    'pageSubtitle' => 'Create and manage RHUConnect staff accounts.',
    'context' => 'Review staff access, filter user records, and keep account status current.',
    'portalLabel' => 'Admin Portal',
    'roleLabel' => 'Administrator',
    'navGroups' => [
        'MAIN MENU' => ['Dashboard', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
        'SYSTEM' => [['label' => 'User Management', 'route' => 'admin.users.index', 'active' => 'admin.users.*']],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="space-y-6">
        <div class="flex justify-end">
            <a href="{{ route('admin.users.create') }}" class="inline-flex items-center justify-center rounded-lg bg-green-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-green-800">
                Add User
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

        <form method="GET" action="{{ route('admin.users.index') }}" class="card-hover grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_14rem_12rem_auto]">
            <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or email"
                class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

            <select name="role" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All roles</option>
                @foreach ($roles as $role)
                    <option value="{{ $role->name }}" @selected(($filters['role'] ?? '') === $role->name)>{{ $role->name }}</option>
                @endforeach
            </select>

            <select name="status" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">
                <option value="">All statuses</option>
                @foreach ($statuses as $status)
                    <option value="{{ $status }}" @selected(($filters['status'] ?? '') === $status)>{{ ucfirst(strtolower($status)) }}</option>
                @endforeach
            </select>

            <button type="submit" class="rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-700">Filter</button>
        </form>

        <div class="card-hover overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm shadow-slate-200/60">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-slate-100">
                    <thead class="bg-slate-50">
                        <tr>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Name</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Email</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Role</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                            <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Created</th>
                            <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 bg-white">
                        @forelse ($users as $user)
                            <tr>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-bold text-slate-900">{{ $user->name }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $user->email }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $user->role?->name ?? 'Unassigned' }}</td>
                                <td class="whitespace-nowrap px-5 py-4">
                                    <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $user->account_status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">
                                        {{ ucfirst(strtolower($user->account_status)) }}
                                    </span>
                                </td>
                                <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $user->created_at?->format('M d, Y') }}</td>
                                <td class="whitespace-nowrap px-5 py-4 text-right text-sm font-semibold">
                                    <div class="flex flex-wrap justify-end gap-2">
                                        <a href="{{ route('admin.users.show', $user) }}" class="rounded-lg px-2.5 py-1.5 text-green-700 transition hover:bg-green-50 hover:text-green-900">View</a>
                                        <a href="{{ route('admin.users.edit', $user) }}" class="rounded-lg px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">Edit</a>
                                        @unless ($user->is(auth()->user()))
                                            <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                                @csrf
                                                @method('PATCH')
                                                <input type="hidden" name="account_status" value="{{ $user->account_status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
                                                <button type="submit" class="rounded-lg px-2.5 py-1.5 text-slate-700 transition hover:bg-slate-50 hover:text-slate-900">
                                                    {{ $user->account_status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}
                                                </button>
                                            </form>
                                        @endunless
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-10 text-center text-sm font-medium text-slate-500">No users found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="border-t border-slate-100 px-5 py-4">
                {{ $users->links() }}
            </div>
        </div>
    </div>
@endsection
