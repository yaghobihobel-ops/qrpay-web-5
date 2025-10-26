@extends('admin.layouts.master')

@push('css')
    <style>
        .risk-metric-card {
            display: flex;
            flex-direction: column;
            gap: .75rem;
        }
        .risk-metric-card .value {
            font-size: 1.75rem;
            font-weight: 700;
        }
        .risk-metric-card .label {
            font-weight: 500;
            color: var(--neutral-600);
        }
        .risk-table-actions {
            display: flex;
            gap: .5rem;
            align-items: center;
            justify-content: flex-end;
        }
        .risk-json-hint {
            font-size: .75rem;
            color: var(--neutral-500);
        }
        .table-wrapper .table td {
            vertical-align: middle;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',[
        'breadcrumbs' => [
            [
                'name'  => __('Dashboard'),
                'url'   => setRoute('admin.dashboard'),
            ]
        ],
        'active' => __('Risk Management')
    ])
@endsection

@section('content')
    <div class="dashboard-area">
        <div class="row g-3 mb-4">
            <div class="col-xl-3 col-md-6">
                <div class="dashbord-item risk-metric-card">
                    <div class="label">{{ __('Pending Decisions') }}</div>
                    <div class="value">{{ $summary['pending'] }}</div>
                    <span class="badge badge--warning">{{ __('Awaiting automation') }}</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="dashbord-item risk-metric-card">
                    <div class="label">{{ __('Manual Reviews') }}</div>
                    <div class="value">{{ $summary['manual_review'] }}</div>
                    <span class="badge badge--info">{{ __('Analyst attention required') }}</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="dashbord-item risk-metric-card">
                    <div class="label">{{ __('Approved Automatically') }}</div>
                    <div class="value text-success">{{ $summary['approved'] }}</div>
                    <span class="badge badge--success">{{ __('Green channel') }}</span>
                </div>
            </div>
            <div class="col-xl-3 col-md-6">
                <div class="dashbord-item risk-metric-card">
                    <div class="label">{{ __('Rejected Automatically') }}</div>
                    <div class="value text-danger">{{ $summary['rejected'] }}</div>
                    <span class="badge badge--danger">{{ __('Hard stop by policy') }}</span>
                </div>
            </div>
        </div>

        <div class="table-area">
            <div class="table-wrapper">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h5 class="title mb-0">{{ __('Dynamic Rules') }}</h5>
                    <button class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#riskRuleCreate">{{ __('Add Rule') }}</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Name') }}</th>
                                <th>{{ __('Event') }}</th>
                                <th>{{ __('Action') }}</th>
                                <th>{{ __('Match') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Updated') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($rules as $rule)
                                <tr>
                                    <td>{{ $rule->name }}</td>
                                    <td><span class="badge badge--info text-uppercase">{{ $rule->event_type }}</span></td>
                                    <td>
                                        @if ($rule->action === 'reject')
                                            <span class="badge badge--danger">{{ __('Reject') }}</span>
                                        @elseif ($rule->action === 'manual_review')
                                            <span class="badge badge--warning">{{ __('Manual Review') }}</span>
                                        @else
                                            <span class="badge badge--success">{{ __('Approve') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ ucfirst($rule->match_type) }}</td>
                                    <td>{{ $rule->priority }}</td>
                                    <td>
                                        @if ($rule->is_active)
                                            <span class="badge badge--success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge--dark">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $rule->updated_at?->diffForHumans() }}</td>
                                    <td>
                                        <div class="risk-table-actions">
                                            <button class="btn btn--sm btn--primary" data-bs-toggle="modal" data-bs-target="#riskRuleEdit-{{ $rule->id }}">{{ __('Edit') }}</button>
                                            <form action="{{ setRoute('admin.risk.rules.delete', $rule->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn--sm btn--danger" type="submit" onclick="return confirm('{{ __('Delete this rule?') }}');">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">{{ __('No risk rules configured yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-area mt-4">
            <div class="table-wrapper">
                <div class="table-header d-flex justify-content-between align-items-center">
                    <h5 class="title mb-0">{{ __('Alert Thresholds') }}</h5>
                    <button class="btn btn--primary" data-bs-toggle="modal" data-bs-target="#thresholdCreate">{{ __('Add Threshold') }}</button>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Metric') }}</th>
                                <th>{{ __('Comparator') }}</th>
                                <th>{{ __('Value') }}</th>
                                <th>{{ __('Decision') }}</th>
                                <th>{{ __('Priority') }}</th>
                                <th>{{ __('Status') }}</th>
                                <th>{{ __('Updated') }}</th>
                                <th class="text-end">{{ __('Actions') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($thresholds as $threshold)
                                <tr>
                                    <td>{{ $threshold->metric }}</td>
                                    <td>{{ strtoupper($threshold->comparator) }}</td>
                                    <td>{{ number_format($threshold->value, 4) }}</td>
                                    <td>
                                        @if ($threshold->decision === 'reject')
                                            <span class="badge badge--danger">{{ __('Reject') }}</span>
                                        @elseif ($threshold->decision === 'manual_review')
                                            <span class="badge badge--warning">{{ __('Manual Review') }}</span>
                                        @else
                                            <span class="badge badge--success">{{ __('Approve') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $threshold->priority }}</td>
                                    <td>
                                        @if ($threshold->is_active)
                                            <span class="badge badge--success">{{ __('Active') }}</span>
                                        @else
                                            <span class="badge badge--dark">{{ __('Disabled') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $threshold->updated_at?->diffForHumans() }}</td>
                                    <td>
                                        <div class="risk-table-actions">
                                            <button class="btn btn--sm btn--primary" data-bs-toggle="modal" data-bs-target="#thresholdEdit-{{ $threshold->id }}">{{ __('Edit') }}</button>
                                            <form action="{{ setRoute('admin.risk.thresholds.delete', $threshold->id) }}" method="POST">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn--sm btn--danger" type="submit" onclick="return confirm('{{ __('Delete this threshold?') }}');">{{ __('Delete') }}</button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">{{ __('No thresholds configured yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="table-area mt-4">
            <div class="table-wrapper">
                <div class="table-header">
                    <h5 class="title mb-0">{{ __('Recent Risk Incidents') }}</h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-striped table-hover">
                        <thead>
                            <tr>
                                <th>{{ __('Transaction') }}</th>
                                <th>{{ __('Type') }}</th>
                                <th>{{ __('Actor') }}</th>
                                <th>{{ __('Decision') }}</th>
                                <th>{{ __('Score') }}</th>
                                <th>{{ __('Created') }}</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($recentIncidents as $incident)
                                <tr>
                                    <td>{{ $incident->trx_id }}</td>
                                    <td>{{ $incident->type }}</td>
                                    <td>
                                        @if ($incident->user)
                                            {{ $incident->user->fullname }}
                                        @elseif ($incident->merchant)
                                            {{ $incident->merchant->name ?? $incident->merchant->business_name }}
                                        @elseif ($incident->agent)
                                            {{ $incident->agent->fullname ?? $incident->agent->name }}
                                        @else
                                            <span class="text-muted">{{ __('N/A') }}</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if ($incident->risk_decision === 'reject')
                                            <span class="badge badge--danger">{{ __('Reject') }}</span>
                                        @elseif ($incident->risk_decision === 'manual_review')
                                            <span class="badge badge--warning">{{ __('Manual Review') }}</span>
                                        @else
                                            <span class="badge badge--success">{{ __('Approve') }}</span>
                                        @endif
                                    </td>
                                    <td>{{ number_format((float) $incident->risk_score, 4) }}</td>
                                    <td>{{ $incident->created_at?->diffForHumans() }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center text-muted">{{ __('No incidents logged yet.') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                {{ get_paginate($recentIncidents) }}
            </div>
        </div>
    </div>

    @include('admin.sections.risk.partials.rule-create-modal')
    @foreach ($rules as $rule)
        @include('admin.sections.risk.partials.rule-edit-modal', ['rule' => $rule])
    @endforeach

    @include('admin.sections.risk.partials.threshold-create-modal')
    @foreach ($thresholds as $threshold)
        @include('admin.sections.risk.partials.threshold-edit-modal', ['threshold' => $threshold])
    @endforeach
@endsection

@push('script')
    <script>
        (function () {
            var modal = '{{ session('modal') }}';
            if (modal) {
                var element = document.getElementById(modal);
                if (element) {
                    var modalInstance = new bootstrap.Modal(element);
                    modalInstance.show();
                }
            }
        })();
    </script>
@endpush
