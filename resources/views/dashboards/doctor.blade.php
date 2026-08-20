@extends('layouts.dashboard', [
    'pageTitle' => $pageTitle,
    'pageSubtitle' => $pageSubtitle,
    'context' => $context,
    'portalLabel' => $portalLabel,
    'roleLabel' => $roleLabel,
    'navGroups' => $navGroups,
    'user' => $user,
])

@section('content')
    @include('dashboards.partials.overview')
@endsection
