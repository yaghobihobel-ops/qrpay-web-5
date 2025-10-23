@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __('Dashboard'),
            'url'   => setRoute('admin.dashboard'),
        ]
    ], 'active' => __($page_title)])
@endsection

@section('content')
    @php
        $statusClasses = [
            \App\Services\Monitoring\HealthCheckService::STATUS_HEALTHY => 'badge badge--success',
            \App\Services\Monitoring\HealthCheckService::STATUS_DEGRADED => 'badge badge--warning',
            \App\Services\Monitoring\HealthCheckService::STATUS_DOWN => 'badge badge--danger',
        ];
    @endphp
    <div class="row mb-24">
        <div class="col-xl-4 col-lg-6 col-md-6">
            <div class="custom-card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h6 class="title">{{ __('Overall status') }}</h6>
                    <a href="{{ setRoute('admin.monitoring.health') }}" class="btn btn--sm btn--primary">{{ __('Refresh') }}</a>
                </div>
                <div class="card-body">
                    <p class="mb-2">{{ __('Combined provider health status based on the most recent checks.') }}</p>
                    <span class="{{ $statusClasses[$overall] ?? 'badge badge--secondary' }} text-uppercase">{{ __($overall) }}</span>
                </div>
            </div>
        </div>
        <div class="col-xl-8 col-lg-6 col-md-6">
            <div class="custom-card h-100">
                <div class="card-header">
                    <h6 class="title">{{ __('Latest provider checks') }}</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive--md">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>{{ __('Provider') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('Latency (ms)') }}</th>
                                    <th>{{ __('HTTP') }}</th>
                                    <th>{{ __('Checked at') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($results as $result)
                                    <tr>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="fw-bold">{{ $result['name'] }}</span>
                                                <small class="text-muted">{{ $result['slug'] }}</small>
                                            </div>
                                        </td>
                                        <td><span class="{{ $statusClasses[$result['status']] ?? 'badge badge--secondary' }} text-uppercase">{{ __($result['status']) }}</span></td>
                                        <td>{{ $result['latency_ms'] ?? __('n/a') }}</td>
                                        <td>{{ $result['status_code'] ?? __('n/a') }}</td>
                                        <td>{{ optional($result['checked_at'])->toDayDateTimeString() }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">{{ __('No provider checks have been executed yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="row mb-24">
        @forelse($history as $provider => $entries)
            <div class="col-xl-6 col-lg-6 mb-24">
                <div class="custom-card h-100">
                    <div class="card-header">
                        <h6 class="title mb-0">{{ __('History: :provider', ['provider' => $provider]) }}</h6>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive--md">
                            <table class="table table-striped">
                                <thead>
                                    <tr>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Latency (ms)') }}</th>
                                        <th>{{ __('Checked at') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($entries as $entry)
                                        <tr>
                                            <td><span class="{{ $statusClasses[$entry->status] ?? 'badge badge--secondary' }} text-uppercase">{{ __($entry->status) }}</span></td>
                                            <td>{{ $entry->latency ?? __('n/a') }}</td>
                                            <td>{{ optional($entry->checked_at)->toDayDateTimeString() }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        @empty
            <div class="col-12">
                <div class="custom-card">
                    <div class="card-body text-center text-muted">
                        {{ __('Historical data will appear here after the first health checks run.') }}
                    </div>
                </div>
            </div>
        @endforelse
    </div>
@endsection
