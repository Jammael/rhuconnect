@extends('layouts.dashboard', [
    'pageTitle' => 'Edit Prenatal Case',
    'pageSubtitle' => 'Track a maternal care patient through their pregnancy.',
    'context' => 'Update the pregnancy timeline, risk details, or case status.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Prenatal Cases', 'link' => route('prenatal-cases.index')],
        ['label' => $case->patient->full_name],
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

        <form method="POST" action="{{ route('prenatal-cases.update', $case) }}" class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @method('PUT')
            @include('prenatal-cases._form', ['submitLabel' => 'Save Changes'])
        </form>
    </div>
@endsection
