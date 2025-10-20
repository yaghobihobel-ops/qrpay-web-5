@extends('admin.layouts.master')

@push('css')
<style>
    .btn--base{
        padding: 11px 25px !important;
    }
</style>

@endpush

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
    <form action="{{ setRoute('admin.exchange.rate.update') }}" method="POST">
        @csrf
        @method('PUT')
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __($page_title) }}</h5>
                  <div class="table-btn-area">
                    @include('admin.components.search-input',[
                        'name'  => 'exchange_rate_search',
                    ])
                    <button class="btn--base btn-loading" type="submit">{{ __("Update Rate") }}</button>
                    {{-- <div class="user-action-btn"> --}}
                        <a href="{{ setRoute('admin.live.exchange.rate.index') }}" class="btn--base"><i class="las la-cog"></i> {{ __("Exchange Rate Api") }}</a>
                    {{-- </div> --}}
                </div>


            </div>
            <div class="table-responsive">
                @include('admin.components.data-table.exchange-rate-table', compact('exchange_rates'))
            </div>
        </div>
    </form>
    {{ get_paginate($exchange_rates) }}
</div>
@endsection

@push('script')
<script>
    itemSearch($("input[name=exchange_rate_search]"),$(".excahnge-rate-search-table"),"{{ setRoute('admin.exchange.rate.search') }}",1);
</script>
@endpush
