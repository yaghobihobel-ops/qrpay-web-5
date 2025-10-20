@extends('admin.layouts.master')

@push('css')
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __(@$page_title),
    ])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">

            <div class="table-header">
                <h5 class="title">{{ $page_title }}</h5>
                @if(count($transactions) > 0)
                <div class="table-btn-area">
                    <a href="{{ setRoute('admin.virtual.card.export.data') }}" class="btn--base"><i class="fas fa-download me-1"></i>{{ __("Export Data") }}</a>
                </div>
            @endif
            </div>
            <div class="table-responsive">
                <table class="custom-table">
                    <thead>
                        <tr>
                            <th>{{ __("TRX ID") }}</th>
                            <th>{{ __("User") }}</th>
                            <th>{{ __("type") }}</th>
                            <th>{{ __("Amount") }}</th>
                            <th>{{ __("charge") }}</th>
                            <th>{{ __("card Amount") }}</th>
                            <th>{{ __(("Status")) }}</th>
                            <th>{{ __("Time") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($transactions ??[]  as $key => $item)

                            <tr>
                                <td>{{ $item->trx_id }}</td>
                                <td>
                                    <a href="{{ setRoute('admin.users.details',$item->user->username) }}"><span class="text-info">{{ $item->user->fullname }}</span></a>
                                </td>

                                <td>{{ __(@$item->remark) }}</td>
                                <td>{{ number_format($item->request_amount,2) }} {{ get_default_currency_code() }}</td>
                                <td>{{ get_amount($item->charge->total_charge,$item->user_wallet->currency->code) }}</td>
                                <td>
                                    @php
                                        $card_number = $item->details->card_info->card_pan?? $item->details->card_info->maskedPan ?? $item->details->card_info->card_number ?? "";
                                    @endphp
                                    @if ($card_number)
                                        @php
                                            $card_pan = str_split($card_number, 4);
                                        @endphp
                                        @foreach($card_pan as $key => $value)
                                            <span class="text--base fw-bold">{{ $value }}</span>
                                        @endforeach
                                    @else
                                        <span class="text--base fw-bold">----</span>
                                        <span class="text--base fw-bold">----</span>
                                        <span class="text--base fw-bold">----</span>
                                        <span class="text--base fw-bold">----</span>
                                    @endif
                                </td>

                                <td>
                                    <span class="{{ $item->stringStatus->class }}">{{ __($item->stringStatus->value) }}</span>
                                </td>
                                <td>{{ $item->created_at->format('d-m-y h:i:s A') }}</td>

                            </tr>
                        @empty
                            @include('admin.components.alerts.empty',['colspan' => 9])
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ get_paginate($transactions) }}
        </div>
    </div>
@endsection

@push('script')
@endpush
