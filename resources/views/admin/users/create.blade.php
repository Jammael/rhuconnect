@extends('layouts.dashboard', [
    'pageTitle' => 'Create User',
    'pageSubtitle' => 'Add a staff account and assign an RHUConnect role.',
    'context' => 'Create a staff account with the correct role and account status.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'User Management', 'link' => route('admin.users.index')],
        ['label' => 'Create User'],
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
    <div class="mx-auto max-w-4xl">
        <form method="POST" action="{{ route('admin.users.store') }}" class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @include('admin.users._form', ['submitLabel' => 'Create Account'])
        </form>
    </div>
@endsection
