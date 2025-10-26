@extends('user.layouts.master')

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Dashboard")])
@endsection

@section('content')
<div class="body-wrapper">
    <div id="user-dashboard-app" data-dashboard='@json($dashboardPayload)'></div>
    <noscript>
        <div class="mt-8 rounded-xl border border-warning bg-warning/10 p-6 text-warning">
            {{ __('For the best experience enable JavaScript to view the interactive dashboard.') }}
        </div>
    </noscript>
</div>
@endsection
