@extends('layouts.dashboard', [
    'pageTitle' => 'Create Patient Record',
    'pageSubtitle' => 'Manage RHUConnect patient information.',
    'context' => 'Capture demographic, contact, guardian, and clinical intake details.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Patient Records', 'link' => route('patients.index')],
        ['label' => 'Create Patient'],
    ],
    'user' => auth()->user(),
])

@section('content')
    <div class="mx-auto max-w-5xl">
        @if ($errors->any())
            <div class="mb-6 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm font-medium text-red-700">
                Please review the highlighted patient fields.
            </div>
        @endif

        <form method="POST" action="{{ route('patients.store') }}" class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @include('patients._form', ['submitLabel' => 'Create Patient Record'])
        </form>
    </div>
@endsection
