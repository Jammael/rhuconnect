@extends('layouts.dashboard', [
    'pageTitle' => 'Edit User',
    'pageSubtitle' => 'Update staff account details, role, status, or password.',
    'context' => 'Keep this staff account accurate without changing the underlying account workflow.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'User Management', 'link' => route('admin.users.index')],
        ['label' => $managedUser->name],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-4xl">
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                Please review the highlighted fields.
            </div>
        @endif

        <form method="POST" action="{{ route('admin.users.update', $managedUser) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @method('PUT')
            @include('admin.users._form', ['submitLabel' => 'Save Changes'])
        </form>
    </div>
@endsection
