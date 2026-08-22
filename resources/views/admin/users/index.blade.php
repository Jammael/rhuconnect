@extends('layouts.dashboard', [
    'pageTitle' => 'User Management',
    'pageSubtitle' => 'Create and manage RHUConnect staff accounts.',
    'context' => 'Review staff access, filter user records, and keep account status current.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'User Management'],
    ],
    'portalLabel' => 'Admin Portal',
    'roleLabel' => 'Administrator',
    'navGroups' => [
        'MAIN MENU' => ['Dashboard', 'Patient Records', 'Online Appointments', 'Smart Queue', 'Doctor Availability', 'Slot Capacity', 'Patient Visit History', 'Reports & Analytics', 'SMS Notifications'],
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

        <div
            class="space-y-4"
            x-data="{
                selectedIds: [],
                userIds: @js($users->getCollection()->pluck('id')->map(fn ($id) => (string) $id)->values()),
                updating: false,
                csrfToken: document.querySelector('meta[name=csrf-token]').content,
                get allSelected() {
                    return this.userIds.length > 0 && this.userIds.every((id) => this.selectedIds.includes(id));
                },
                get someSelected() {
                    return this.selectedIds.length > 0 && ! this.allSelected;
                },
                init() {
                    this.$watch('selectedIds', () => this.$nextTick(() => {
                        if (this.$refs.selectAll) this.$refs.selectAll.indeterminate = this.someSelected;
                    }));
                },
                toggleAll() {
                    this.selectedIds = this.allSelected ? [] : [...this.userIds];
                },
                async updateSelected(accountStatus) {
                    if (! this.selectedIds.length || this.updating) return;

                    this.updating = true;

                    try {
                        const response = await fetch('{{ route('admin.users.bulk-status') }}', {
                            method: 'PATCH',
                            headers: {
                                'Accept': 'application/json',
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': this.csrfToken,
                            },
                            body: JSON.stringify({ user_ids: this.selectedIds.map(Number), account_status: accountStatus }),
                        });
                        const data = await response.json();

                        if (! response.ok) throw new Error(data.message ?? 'Unable to update selected users.');

                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: data.message, type: data.self_excluded ? 'warning' : 'success' },
                        }));
                        this.selectedIds = [];
                        setTimeout(() => window.location.reload(), 700);
                    } catch (error) {
                        window.dispatchEvent(new CustomEvent('toast', {
                            detail: { message: error.message ?? 'Unable to update selected users.', type: 'error' },
                        }));
                    } finally {
                        this.updating = false;
                    }
                },
            }"
        >
            <div
                x-cloak
                x-show="selectedIds.length > 0"
                x-transition:enter="transition ease-out duration-150"
                x-transition:enter-start="-translate-y-1 opacity-0"
                x-transition:enter-end="translate-y-0 opacity-100"
                class="flex flex-col gap-3 rounded-xl border border-green-200 bg-green-50 p-4 sm:flex-row sm:items-center sm:justify-between"
            >
                <p class="text-sm font-extrabold text-green-800"><span x-text="selectedIds.length"></span> selected</p>
                <div class="flex flex-wrap items-center gap-2">
                    <button type="button" class="rounded-lg bg-green-700 px-3.5 py-2 text-xs font-extrabold text-white transition hover:bg-green-800 disabled:cursor-not-allowed disabled:opacity-60" x-on:click="updateSelected('ACTIVE')" x-bind:disabled="updating">Activate Selected</button>
                    <button type="button" class="rounded-lg bg-slate-800 px-3.5 py-2 text-xs font-extrabold text-white transition hover:bg-slate-700 disabled:cursor-not-allowed disabled:opacity-60" x-on:click="updateSelected('INACTIVE')" x-bind:disabled="updating">Deactivate Selected</button>
                    <button type="button" class="rounded-lg px-2 py-2 text-xs font-bold text-slate-600 transition hover:text-green-700" x-on:click="selectedIds = []">Clear selection</button>
                </div>
            </div>

            <form method="GET" action="{{ route('admin.users.index') }}" class="card-hover grid gap-4 rounded-xl border border-slate-200 bg-white p-5 shadow-sm shadow-slate-200/60 md:grid-cols-[1fr_14rem_12rem_auto]">
                <input name="search" type="search" value="{{ $filters['search'] ?? '' }}" placeholder="Search name or email" class="rounded-lg border-slate-300 shadow-sm focus:border-green-700 focus:ring-green-700">

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
                                <th class="w-12 px-5 py-3 text-left">
                                    <input x-ref="selectAll" type="checkbox" class="rounded border-slate-300 text-green-700 focus:ring-green-600" x-bind:checked="allSelected" x-on:change="toggleAll()" aria-label="Select all users on this page">
                                </th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">User</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Role</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Status</th>
                                <th class="px-5 py-3 text-left text-xs font-extrabold uppercase tracking-wide text-slate-400">Created</th>
                                <th class="px-5 py-3 text-right text-xs font-extrabold uppercase tracking-wide text-slate-400">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 bg-white">
                            @forelse ($users as $user)
                                @php
                                    $initials = collect(explode(' ', trim($user->name)))
                                        ->filter()
                                        ->take(2)
                                        ->map(fn ($part) => mb_substr($part, 0, 1))
                                        ->implode('');
                                @endphp
                                <tr class="transition-colors duration-150 hover:bg-slate-50">
                                    <td class="px-5 py-4">
                                        <input type="checkbox" value="{{ $user->id }}" x-model="selectedIds" class="rounded border-slate-300 text-green-700 focus:ring-green-600" aria-label="Select {{ $user->name }}">
                                    </td>
                                    <td class="min-w-64 px-5 py-4">
                                        <div class="flex items-center gap-3">
                                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-green-100 text-sm font-extrabold text-green-700">{{ $initials }}</span>
                                            <span class="min-w-0">
                                                <span class="block truncate text-sm font-extrabold text-slate-900">{{ $user->name }}</span>
                                                <span class="mt-0.5 block truncate text-sm font-medium text-slate-500">{{ $user->email }}</span>
                                            </span>
                                        </div>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $user->role?->name ?? 'Unassigned' }}</td>
                                    <td class="whitespace-nowrap px-5 py-4">
                                        <span class="inline-flex rounded-full px-3 py-1 text-xs font-extrabold {{ $user->account_status === 'ACTIVE' ? 'bg-green-100 text-green-700' : 'bg-slate-100 text-slate-600' }}">{{ ucfirst(strtolower($user->account_status)) }}</span>
                                    </td>
                                    <td class="whitespace-nowrap px-5 py-4 text-sm font-medium text-slate-500">{{ $user->created_at?->format('M d, Y') }}</td>
                                    <td class="whitespace-nowrap px-5 py-4 text-right">
                                        <div class="relative inline-block" x-data="{ menuOpen: false }">
                                            <button type="button" class="flex h-9 w-9 items-center justify-center rounded-lg text-slate-400 transition hover:bg-green-50 hover:text-green-700 focus:outline-none focus:ring-2 focus:ring-green-500" x-on:click="menuOpen = ! menuOpen" x-bind:aria-expanded="menuOpen.toString()" aria-label="Open actions for {{ $user->name }}">
                                                <svg class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="5" cy="12" r="1" /><circle cx="12" cy="12" r="1" /><circle cx="19" cy="12" r="1" /></svg>
                                            </button>
                                            <div x-cloak x-show="menuOpen" x-on:click.outside="menuOpen = false" x-transition:enter="transition ease-out duration-150" x-transition:enter-start="translate-y-1 opacity-0" x-transition:enter-end="translate-y-0 opacity-100" class="absolute right-0 z-30 mt-2 w-36 overflow-hidden rounded-xl border border-slate-200 bg-white p-1.5 text-left shadow-xl shadow-slate-200/70">
                                                <a href="{{ route('admin.users.show', $user) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-green-700">View</a>
                                                <a href="{{ route('admin.users.edit', $user) }}" class="block rounded-lg px-3 py-2 text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-green-700">Edit</a>
                                                @unless ($user->is(auth()->user()))
                                                    <form method="POST" action="{{ route('admin.users.status', $user) }}">
                                                        @csrf
                                                        @method('PATCH')
                                                        <input type="hidden" name="account_status" value="{{ $user->account_status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE' }}">
                                                        <button type="submit" class="block w-full rounded-lg px-3 py-2 text-left text-sm font-semibold text-slate-600 transition hover:bg-slate-50 hover:text-green-700">{{ $user->account_status === 'ACTIVE' ? 'Deactivate' : 'Activate' }}</button>
                                                    </form>
                                                @endunless
                                            </div>
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
                @if ($users->hasPages())
                    <nav class="flex flex-wrap items-center justify-between gap-3 border-t border-slate-100 px-5 py-4" aria-label="User pagination">
                        <a href="{{ $users->previousPageUrl() }}" @class(['pointer-events-none opacity-40' => $users->onFirstPage(), 'inline-flex rounded-full border border-slate-200 px-3 py-1.5 text-sm font-bold text-slate-600 transition hover:border-green-200 hover:bg-green-50 hover:text-green-700'])>Previous</a>
                        <div class="flex items-center gap-1">
                            @foreach ($users->getUrlRange(max(1, $users->currentPage() - 1), min($users->lastPage(), $users->currentPage() + 1)) as $page => $url)
                                <a href="{{ $url }}" @class(['inline-flex h-9 min-w-9 items-center justify-center rounded-full px-2 text-sm font-bold transition', 'bg-green-700 text-white shadow-sm' => $page === $users->currentPage(), 'text-slate-600 hover:bg-green-50 hover:text-green-700' => $page !== $users->currentPage()])>{{ $page }}</a>
                            @endforeach
                        </div>
                        <a href="{{ $users->nextPageUrl() }}" @class(['pointer-events-none opacity-40' => ! $users->hasMorePages(), 'inline-flex rounded-full border border-slate-200 px-3 py-1.5 text-sm font-bold text-slate-600 transition hover:border-green-200 hover:bg-green-50 hover:text-green-700'])>Next</a>
                    </nav>
                @endif
            </div>
        </div>
    </div>
@endsection
