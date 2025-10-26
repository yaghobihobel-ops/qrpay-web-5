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
<div class="body-wrapper">
    <div class="row mb-none-30">
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Total tickets') }}</span>
                    <h3 class="mt-2">{{ number_format($totalTickets) }}</h3>
                    <p class="mb-0 text-muted">{{ __('All support requests recorded.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Open tickets') }}</span>
                    <h3 class="mt-2">{{ number_format($openTickets) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Awaiting resolution or response.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Solved tickets') }}</span>
                    <h3 class="mt-2">{{ number_format($solvedTickets) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Closed by support specialists.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Customer satisfaction') }}</span>
                    <h3 class="mt-2">{{ $averageSatisfaction ? number_format($averageSatisfaction, 2) : __('N/A') }}</h3>
                    <p class="mb-0 text-muted">{{ __('Average rating (1-5) across solved tickets.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30">
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Avg. first response (min)') }}</span>
                    <h3 class="mt-2">{{ $firstResponseMinutes !== null ? number_format($firstResponseMinutes, 2) : __('N/A') }}</h3>
                    <p class="mb-0 text-muted">{{ __('Target: :target min', ['target' => $slaFirstTarget]) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Avg. resolution (hrs)') }}</span>
                    <h3 class="mt-2">{{ $resolutionHours !== null ? number_format($resolutionHours, 2) : __('N/A') }}</h3>
                    <p class="mb-0 text-muted">{{ __('Target: :target hrs', ['target' => round($slaResolutionTarget / 60, 1)]) }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('First response SLA breaches') }}</span>
                    <h3 class="mt-2 text-danger">{{ number_format($firstResponseBreaches) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Tickets exceeding first response target.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Resolution SLA breaches') }}</span>
                    <h3 class="mt-2 text-danger">{{ number_format($resolutionBreaches) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Tickets exceeding resolution target.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30">
        <div class="col-xl-4 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Bot sessions') }}</span>
                    <h3 class="mt-2">{{ number_format($sessionCount) }}</h3>
                    <p class="mb-2 text-muted">{{ __('Automated conversations started with SupportBot.') }}</p>
                    <ul class="list-unstyled mb-0 small text-muted">
                        <li>{{ __('Handoff suggested: :count', ['count' => number_format($handoffSessions)]) }}</li>
                        <li>{{ __('Tickets created from bot: :count', ['count' => number_format($ticketsFromBot)]) }}</li>
                        <li>{{ __('Deflection rate: :value%', ['value' => $deflectionRate !== null ? number_format($deflectionRate, 1) : '0']) }}</li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('14 day ticket trend') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 support-trend-list">
                        @forelse($trendData as $day)
                            <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                <span>{{ $day['label'] }}</span>
                                <span class="fw-semibold">{{ $day['value'] }}</span>
                            </li>
                        @empty
                            <li class="text-muted text-center py-3">{{ __('Not enough data yet.') }}</li>
                        @endforelse
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-4 mb-30">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('Recent tickets') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0 align-middle">
                            <thead>
                                <tr>
                                    <th>{{ __('Token') }}</th>
                                    <th>{{ __('Subject') }}</th>
                                    <th>{{ __('Status') }}</th>
                                    <th>{{ __('First response') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($recentTickets as $ticket)
                                    <tr>
                                        <td>{{ $ticket->token }}</td>
                                        <td>
                                            <strong>{{ $ticket->subject }}</strong>
                                            @if($ticket->supportBotSession)
                                                <div class="text-muted small">{{ __('Bot session: :id', ['id' => $ticket->supportBotSession->session_id]) }}</div>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $ticket->stringStatus->class ?? '' }}">{{ __($ticket->stringStatus->value ?? 'N/A') }}</span></td>
                                        <td>
                                            @if($ticket->first_response_at)
                                                {{ $ticket->first_response_at->diffForHumans($ticket->created_at, true, true) }}
                                            @else
                                                <span class="text-muted">{{ __('Pending') }}</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="4" class="text-center text-muted py-4">{{ __('No tickets have been created yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
    <style>
        .support-trend-list li:last-child {
            border-bottom: none;
        }
        .support-trend-list li span:first-child {
            font-weight: 500;
        }
    </style>
@endpush
