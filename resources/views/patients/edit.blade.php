@extends('layouts.dashboard', [
    'pageTitle' => 'Edit Patient Record',
    'pageSubtitle' => 'Manage RHUConnect patient information.',
    'context' => 'Keep demographic, contact, guardian, and clinical intake details current.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Patient Records', 'link' => route('patients.index')],
        ['label' => $patient->full_name],
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

        <form method="POST" action="{{ route('patients.update', $patient) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @method('PUT')
            @include('patients._form', ['submitLabel' => 'Save Patient Record'])
        </form>
    </div>
@endsection
