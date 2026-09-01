@extends('layouts.dashboard', [
    'pageTitle' => 'New Prenatal Case',
    'pageSubtitle' => 'Track a maternal care patient through their pregnancy.',
    'context' => 'Record the pregnancy timeline and risk details for ongoing care.',
    'breadcrumb' => [
        ['label' => 'Dashboard', 'link' => route('dashboard')],
        ['label' => 'Prenatal Cases', 'link' => route('prenatal-cases.index')],
        ['label' => 'New Case'],
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

        <form method="POST" action="{{ route('prenatal-cases.store') }}" class="card-hover rounded-xl border border-slate-200 bg-white p-6 shadow-sm shadow-slate-200/60">
            @include('prenatal-cases._form', ['submitLabel' => 'Create Case'])
        </form>
    </div>
@endsection
