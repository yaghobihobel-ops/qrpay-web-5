@extends('admin.layouts.master')

@section('content')
<div class="body-wrapper">
    <div class="card">
        <div class="card-header d-flex flex-wrap align-items-center justify-content-between gap-2">
            <h4 class="card-title mb-0">{{ __('Pricing rules') }}</h4>
            <div class="d-flex flex-wrap align-items-center gap-2">
                <form method="GET" action="{{ route('admin.pricing-rules.index') }}" class="d-flex align-items-center gap-2">
                    <input type="text" name="search" value="{{ request('search') }}" class="form--control" placeholder="{{ __('Search provider or currency') }}">
                    <button type="submit" class="btn btn--primary">{{ __('Search') }}</button>
                </form>
                <a href="{{ route('admin.pricing-rules.create') }}" class="btn btn--success">
                    <i class="las la-plus me-1"></i> {{ __('New rule') }}
                </a>
            </div>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table--responsive--md">
                    <thead>
                        <tr>
                            <th>{{ __('Name') }}</th>
                            <th>{{ __('Provider') }}</th>
                            <th>{{ __('Currency') }}</th>
                            <th>{{ __('Transaction type') }}</th>
                            <th>{{ __('Fee type') }}</th>
                            <th>{{ __('Priority') }}</th>
                            <th>{{ __('Active') }}</th>
                            <th>{{ __('Experiment / Variant') }}</th>
                            <th>{{ __('Updated at') }}</th>
                            <th>{{ __('Actions') }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rules as $rule)
                            <tr>
                                <td data-label="{{ __('Name') }}">
                                    <span class="fw-bold">{{ $rule->name }}</span>
                                    <div class="text-muted small">{{ $rule->fee_tiers_count }} {{ __('tiers') }}</div>
                                </td>
                                <td data-label="{{ __('Provider') }}">{{ strtoupper($rule->provider) }}</td>
                                <td data-label="{{ __('Currency') }}">{{ strtoupper($rule->currency) }}</td>
                                <td data-label="{{ __('Transaction type') }}">{{ $rule->transaction_type }}</td>
                                <td data-label="{{ __('Fee type') }}">{{ $rule->fee_type }}</td>
                                <td data-label="{{ __('Priority') }}">{{ $rule->priority }}</td>
                                <td data-label="{{ __('Active') }}">
                                    @if($rule->active)
                                        <span class="badge badge--success">{{ __('Yes') }}</span>
                                    @else
                                        <span class="badge badge--danger">{{ __('No') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Experiment / Variant') }}">
                                    @if($rule->experiment)
                                        <span class="badge badge--info">{{ $rule->experiment }} / {{ $rule->variant }}</span>
                                    @else
                                        <span class="text-muted">{{ __('Default') }}</span>
                                    @endif
                                </td>
                                <td data-label="{{ __('Updated at') }}">{{ $rule->updated_at?->format('Y-m-d H:i') }}</td>
                                <td class="text-end" data-label="{{ __('Actions') }}">
                                    <div class="d-flex gap-2 justify-content-end">
                                        <a class="btn btn--primary btn-sm" href="{{ route('admin.pricing-rules.edit', $rule) }}">
                                            <i class="las la-pen"></i>
                                        </a>
                                        <button type="button" class="btn btn--danger btn-sm" onclick="openDeleteModal('{{ route('admin.pricing-rules.destroy', $rule) }}','pricing-rule-{{ $rule->id }}','{{ __('Are you sure you want to delete this pricing rule?') }}','{{ __('Delete') }}')">
                                            <i class="las la-trash"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-4">{{ __('No pricing rules configured yet.') }}</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer">
            {{ $rules->links() }}
        </div>
    </div>
</div>
@endsection
