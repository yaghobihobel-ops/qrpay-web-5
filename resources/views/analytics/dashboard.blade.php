@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="row mb-4">
        <div class="col">
            <h1 class="h3">Realtime Payments KPI Dashboard</h1>
            <p class="text-muted">Live operational metrics sourced from the analytics datastore and cached for fast refresh.</p>
        </div>
    </div>
    <div class="row g-3 mb-5">
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Conversion Rate</h2>
                    <p class="display-6">{{ $snapshot['conversion_rate'] ?? 0 }}%</p>
                    <p class="text-muted small">Completed transactions vs. initiated transactions (last minute).</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">Provider Latency (P95)</h2>
                    <p class="display-6">{{ $snapshot['provider_latency_ms'] ?? 0 }} ms</p>
                    <p class="text-muted small">Recent 95th percentile latency collected from webhook callbacks.</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h5">User Error Rate</h2>
                    <p class="display-6">{{ $snapshot['user_error_rate'] ?? 0 }}%</p>
                    <p class="text-muted small">Percentage of user-driven failures over total errors (last minute).</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        @if($metabaseUrl)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Metabase Overview</h2>
                        <iframe src="{{ $metabaseUrl }}" class="w-100 border-0" style="min-height: 420px;" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        @endif

        @if($grafanaUrl)
            <div class="col-md-6">
                <div class="card h-100 shadow-sm">
                    <div class="card-body">
                        <h2 class="h5">Grafana Realtime Panel</h2>
                        <iframe src="{{ $grafanaUrl }}" class="w-100 border-0" style="min-height: 420px;" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        @endif
    </div>
</div>
@endsection
