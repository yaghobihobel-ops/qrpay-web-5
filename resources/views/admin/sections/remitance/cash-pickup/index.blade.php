@extends('admin.layouts.master')

@push('css')
    <style>
        .fileholder {
            min-height: 194px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 150px !important;
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
    ], 'active' => __("Setup Cash Pickup")])
@endsection

@section('content')
    <div class="table-area">
        <div class="table-wrapper">
            <div class="table-header">
                <h5 class="title">{{ __("cash Pickup") }}</h5>
                <div class="table-btn-area">
                    @include('admin.components.search-input',[
                        'name'  => 'cashpickup_search',
                    ])
                    @include('admin.components.link.add-default',[
                        'text'          => __("Add New"),
                        'href'          => "#cash-pickup-add",
                        'class'         => "modal-btn",
                        'permission'    => "admin.remitance.cash.pickup.store",
                    ])
                </div>
            </div>
            <div class="table-responsive">
                @include('admin.components.data-table.cash-pickup-table',[
                    'data'  => $cashPickups
                ])
            </div>
        </div>
        {{ get_paginate($cashPickups) }}
    </div>

    {{-- Currency Edit Modal --}}
    @include('admin.components.modals.edit-cash-pickup')

    {{-- Currency Add Modal --}}
    @include('admin.components.modals.cash-pickup-add')

@endsection

@push('script')
    <script>
        function keyPressCurrencyView(select) {
            var selectedValue = $(select);
            selectedValue.parents("form").find("input[name=code],input[name=currency_code]").keyup(function(){
                selectedValue.parents("form").find(".selcted-currency").text($(this).val());
            });
        }

        $(".delete-modal-button").click(function(){
            var oldData = JSON.parse($(this).parents("tr").attr("data-item"));
            var actionRoute =  "{{ setRoute('admin.remitance.cash.pickup.delete') }}";
            var target      = oldData.id;
            var message     = `Are you sure to delete <strong>${oldData.name}</strong>?`;
            openDeleteModal(actionRoute,target,message);
        });

        itemSearch($("input[name=cashpickup_search]"),$(".cash-search-table"),"{{ setRoute('admin.remitance.cash.pickup.search') }}",1);
    </script>
@endpush
