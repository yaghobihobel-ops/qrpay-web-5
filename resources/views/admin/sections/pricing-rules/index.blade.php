@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ]
    ], 'active' => __($page_title)])
@endsection

@section('content')
<div class="table-area">
    <div class="table-wrapper">
        <div class="table-header d-flex justify-content-between align-items-center">
            <h5 class="title mb-0">{{ __($page_title) }}</h5>
            <a href="{{ setRoute('admin.pricing.rules.create') }}" class="btn--base"><i class="las la-plus"></i> {{ __('New Rule') }}</a>
        </div>
        <div class="table-responsive">
            <table class="custom-table">
                <thead>
                    <tr>
                        <th>{{ __('Name') }}</th>
                        <th>{{ __('Provider') }}</th>
                        <th>{{ __('Currency') }}</th>
                        <th>{{ __('Transaction Type') }}</th>
                        <th>{{ __('User Level') }}</th>
                        <th>{{ __('Base Currency') }}</th>
                        <th>{{ __('Status') }}</th>
                        <th>{{ __('Updated At') }}</th>
                        <th>{{ __('Action') }}</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($rules as $rule)
                    <tr>
                        <td>{{ $rule->name }}</td>
                        <td>{{ $rule->provider ?? __('Any') }}</td>
                        <td>{{ $rule->currency ?? __('Any') }}</td>
                        <td>{{ $rule->transaction_type }}</td>
                        <td>{{ $rule->user_level }}</td>
                        <td>{{ $rule->base_currency }}</td>
                        <td>
                            <span class="badge {{ $rule->status ? 'badge--success' : 'badge--danger' }}">
                                {{ $rule->status ? __('Active') : __('Disabled') }}
                            </span>
                        </td>
                        <td>{{ $rule->updated_at?->format('Y-m-d H:i') }}</td>
                        <td>
                            <div class="d-flex gap-2">
                                <a href="{{ setRoute('admin.pricing.rules.edit', $rule) }}" class="btn btn-sm btn--primary"><i class="las la-pen"></i></a>
                                <form action="{{ setRoute('admin.pricing.rules.destroy', $rule) }}" method="POST" onsubmit="return confirm('{{ __('Are you sure you want to delete this rule?') }}');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn--danger"><i class="las la-trash"></i></button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="9" class="text-center text-muted">{{ __('No pricing rules have been configured yet.') }}</td>
                    </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
    {{ get_paginate($rules) }}
</div>
@endsection
