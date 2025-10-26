@extends('admin.layouts.master')

@section('page-title')
    <div class="page-title">
        <h5 class="title">{{ __('Audit Logs') }}</h5>
    </div>
@endsection

@section('breadcrumb')
    @component('admin.components.breadcrumb')
        <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">{{ __('Dashboard') }}</a></li>
        <li class="breadcrumb-item active" aria-current="page">{{ __('Audit Logs') }}</li>
    @endcomponent
@endsection

@section('content')
    <div class="container-fluid">
        <div class="row">
            <div class="col-12">
                <div class="card custom-card">
                    <div class="card-header border-bottom-0 d-flex justify-content-between align-items-center flex-wrap gap-2">
                        <h5 class="card-title mb-0">{{ __('Compliance Audit Trail') }}</h5>
                    </div>
                    <div class="card-body">
                        <form method="GET" action="{{ route('admin.audit.logs.index') }}" class="row g-3 mb-4">
                            <div class="col-md-3">
                                <label class="form-label" for="action">{{ __('Action') }}</label>
                                <input type="text" class="form-control" name="action" id="action" value="{{ $filters['action'] }}" placeholder="{{ __('Search action...') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="user_id">{{ __('User ID') }}</label>
                                <input type="text" class="form-control" name="user_id" id="user_id" value="{{ $filters['user_id'] }}" placeholder="{{ __('User identifier') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="ip_address">{{ __('IP Address') }}</label>
                                <input type="text" class="form-control" name="ip_address" id="ip_address" value="{{ $filters['ip_address'] }}" placeholder="{{ __('Search IP...') }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="status">{{ __('Status') }}</label>
                                <select class="form-select" name="status" id="status">
                                    <option value="">{{ __('Any status') }}</option>
                                    <option value="success" @selected($filters['status'] === 'success')>{{ __('Success') }}</option>
                                    <option value="failed" @selected($filters['status'] === 'failed')>{{ __('Failed') }}</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date_from">{{ __('Date from') }}</label>
                                <input type="date" class="form-control" name="date_from" id="date_from" value="{{ $filters['date_from'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="date_to">{{ __('Date to') }}</label>
                                <input type="date" class="form-control" name="date_to" id="date_to" value="{{ $filters['date_to'] }}">
                            </div>
                            <div class="col-md-3">
                                <label class="form-label" for="per_page">{{ __('Per page') }}</label>
                                <input type="number" min="1" class="form-control" name="per_page" id="per_page" value="{{ request()->integer('per_page', $logs->perPage()) }}">
                            </div>
                            <div class="col-md-3 d-flex align-items-end gap-2">
                                <button class="btn btn--primary w-100" type="submit">{{ __('Filter') }}</button>
                                <a class="btn btn--danger w-100" href="{{ route('admin.audit.logs.index') }}">{{ __('Reset') }}</a>
                            </div>
                        </form>

                        <div class="table-responsive">
                            <table class="table table--light style-two">
                                <thead>
                                    <tr>
                                        <th>{{ __('Timestamp') }}</th>
                                        <th>{{ __('Action') }}</th>
                                        <th>{{ __('User') }}</th>
                                        <th>{{ __('IP Address') }}</th>
                                        <th>{{ __('Status') }}</th>
                                        <th>{{ __('Payload') }}</th>
                                        <th>{{ __('Result') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                @forelse($logs as $log)
                                    <tr>
                                        <td>{{ $log->created_at->format('Y-m-d H:i:s') }}</td>
                                        <td><span class="badge bg-primary">{{ $log->action }}</span></td>
                                        <td>
                                            @if($log->user)
                                                @php
                                                    $identifier = method_exists($log->user, 'getAuthIdentifier') ? $log->user->getAuthIdentifier() : ($log->user->id ?? null);
                                                @endphp
                                                <div class="fw-bold">{{ $log->user->name ?? $log->user->username ?? __('Unknown user') }}</div>
                                                <div class="text-muted small">{{ $log->user->email ?? ($identifier ? '#' . $identifier : __('N/A')) }}</div>
                                            @else
                                                <span class="text-muted">{{ __('System') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ $log->ip_address ?? '—' }}</td>
                                        <td>
                                            @if($log->status)
                                                <span class="badge {{ $log->status === 'success' ? 'bg-success' : 'bg-warning' }}">{{ ucfirst($log->status) }}</span>
                                            @else
                                                <span class="badge bg-secondary">{{ __('N/A') }}</span>
                                            @endif
                                        </td>
                                        <td class="text-break">
                                            <pre class="mb-0 small">{{ json_encode($log->payload ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </td>
                                        <td class="text-break">
                                            <pre class="mb-0 small">{{ json_encode($log->result ?? [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted">{{ __('No audit records found for the selected filters.') }}</td>
                                    </tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>

                        <div class="mt-3">
                            {{ $logs->links() }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
