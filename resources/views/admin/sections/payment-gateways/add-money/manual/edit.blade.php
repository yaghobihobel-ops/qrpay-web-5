@extends('admin.layouts.master')
@php
   $pricison = $payment_gateway->crypto == 1 ? 8 :2;
@endphp
@push('css')
    <style>
        .fileholder {
            min-height: 300px !important;
        }

        .fileholder-files-view-wrp.accept-single-file .fileholder-single-file-view,.fileholder-files-view-wrp.fileholder-perview-single .fileholder-single-file-view{
            height: 256px !important;
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
    ], 'active' => __("Add Money")])
@endsection

@section('content')
    <form action="{{ setRoute('admin.payment.gateway.update',['add-money','manual',$payment_gateway->alias]) }}" method="POST" enctype="multipart/form-data">
        @csrf
        @method("PUT")
        <div class="custom-card">
            <div class="card-header">
                <h6 class="title">{{ $page_title }}</h6>
            </div>
            <div class="card-body">
                <div class="row mb-10-none">
                    <div class="col-xl-3 col-lg-3 form-group">
                        @include('admin.components.form.input-file',[
                            'label'             => __("Gateway Image"),
                            'name'              => "image",
                            'class'             => "file-holder",
                            'old_files_path'    => files_asset_path('payment-gateways'),
                            'old_files'         => $payment_gateway->image,
                        ])
                    </div>
                    <div class="col-xl-9 col-lg-9">
                        <div class="form-group">
                            @include('admin.components.form.input',[
                                'label'         => __("Gateway Name*"),
                                'name'          => "gateway_name",
                                'value'         => old('gateway_name',$payment_gateway->name),
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                            'label'     =>__( "Currency Code")."*",
                                'name'          => "currency_code",
                                'value'         => old('currency_code',$payment_gateway->currencies->first()->currency_code),
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.input',[
                               'label'     =>__( "Currency Symbol")."*",
                                'name'          => "currency_symbol",
                                'value'         => old('currency_symbol',$payment_gateway->currencies->first()->currency_symbol),
                            ])
                        </div>
                        <div class="form-group">
                            @include('admin.components.form.switcher',[
                                'label'         => __("type"),
                                'name'          => 'crypto',
                                'value'         => old('crypto',$payment_gateway->crypto),
                                'options'       => [__("CRYPTO") => 1, __("FIAT") => 0],

                            ])
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="custom-card mt-15">
            <div class="card-body">
                <div class="row">
                    <div class="col-xl-4 col-lg-4 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Amount Limit") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="form-group">
                                            @include('admin.components.form.input-amount',[
                                                  'label'         => __("Minimum")."*",
                                                'name'          => "min_limit",
                                                'value'         => old("min_limit",get_amount($payment_gateway->currencies->first()->min_limit,null,$pricison)),
                                                'currency'      => $payment_gateway->currencies->first()->currency_code,
                                            ])
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="form-group">
                                            @include('admin.components.form.input-amount',[
                                                 'label'         => __("Maximum")."*",
                                                'name'          => "max_limit",
                                                'value'         => old("max_limit",get_amount($payment_gateway->currencies->first()->max_limit,null,$pricison)),
                                                'currency'      => $payment_gateway->currencies->first()->currency_code,
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Charges") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="form-group">
                                            @include('admin.components.form.input-amount',[
                                                'label'         => __("Fixed")."*",
                                                'name'          => "fixed_charge",
                                                'value'         => old("fixed_charge",get_amount($payment_gateway->currencies->first()->fixed_charge,null,$pricison)),
                                                'currency'      => $payment_gateway->currencies->first()->currency_code,
                                            ])
                                        </div>
                                    </div>
                                    <div class="col-xl-6 col-lg-6">
                                        <div class="form-group">
                                            @include('admin.components.form.input-amount',[
                                                'label'         => __("Percent")."*",
                                                'name'          => "percent_charge",
                                                'value'         => old("percent_charge",get_amount($payment_gateway->currencies->first()->percent_charge,null,$pricison)),
                                                'currency'      => $payment_gateway->currencies->first()->currency_code,
                                            ])
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-4 mb-10">
                        <div class="custom-inner-card">
                            <div class="card-inner-header">
                                <h5 class="title">{{ __("Rate") }}</h5>
                            </div>
                            <div class="card-inner-body">
                                <div class="row">
                                    <div class="col-xl-12 col-lg-12">
                                        <div class="form-group">
                                            <label>{{ __("Rate") }}</label>
                                            <div class="input-group">
                                                <span class="input-group-text append ">1 &nbsp; <span class="default-currency">{{ get_default_currency_code($default_currency) }}</span>&nbsp; = </span>
                                                <input type="te" class="form--control" value="{{ old("rate",get_amount($payment_gateway->currencies->first()->rate,null,$pricison)) }}" name="rate" placeholder="Type Here...">
                                                <span class="input-group-text currency">{{ $payment_gateway->currencies->first()->currency_code }}</span>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.form.input-text-rich',[
                           'label'     =>__( "Instructions")."*",
                            'name'      => "desc",
                            'value'     => old("desc",$payment_gateway->desc),
                        ])
                    </div>
                    <div class="col-xl-12 col-lg-12 form-group">
                        <div class="custom-inner-card input-field-generator" data-source="manual_gateway_input_fields">
                            <div class="card-inner-header">
                                <h6 class="title">{{ __("Collect Data") }}</h6>
                                <button type="button" class="btn--base add-row-btn"><i class="fas fa-plus"></i> {{ __("Add") }}</button>
                            </div>
                            <div class="card-inner-body">
                                <div class="results">
                                    @foreach ($payment_gateway->input_fields as $item)
                                        <div class="row add-row-wrapper align-items-end">
                                            <div class="col-xl-3 col-lg-3 form-group">
                                                @include('admin.components.form.input',[
                                                    'label'     =>__( "Field Name*"),
                                                    'name'      => "label[]",
                                                    'attribute' => "required",
                                                    'value'     => $item->label,
                                                ])
                                            </div>
                                            <div class="col-xl-2 col-lg-2 form-group">
                                                @php
                                                    $selectOptions = ['text' => "Input Text", 'file' => "File", 'textarea' => "Textarea"];
                                                @endphp
                                                <label>{{ __("Field Types*") }}</label>
                                                <select class="form--control nice-select field-input-type" name="input_type[]" data-old="{{ $item->type }}">
                                                    @foreach ($selectOptions as $key => $value)
                                                        <option value="{{ $key }}" {{ ($key == $item->type) ? "selected" : "" }}>{{ $value }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="field_type_input col-lg-4 col-xl-4">
                                                @if ($item->type == "file")
                                                    <div class="row">
                                                        <div class="col-xl-6 col-lg-6 form-group">
                                                            @include('admin.components.form.input',[
                                                                'label'         => __("Max File Size (mb)*"),
                                                                'name'          => "file_max_size[]",
                                                                'type'          => "number",
                                                                'attribute'     => "required",
                                                                'value'         => old('file_max_size[]',$item->validation->max),
                                                                'placeholder'   => __("ex").": 10",
                                                            ])
                                                        </div>
                                                        <div class="col-xl-6 col-lg-6 form-group">
                                                            @include('admin.components.form.input',[
                                                                'label'         => __("File Extension*"),
                                                                'name'          => "file_extensions[]",
                                                                'attribute'     => "required",
                                                                'value'         => old('file_extensions[]',implode(",",$item->validation->mimes)),
                                                                'placeholder'   => __("ex").": jpg, png, pdf",
                                                            ])
                                                        </div>
                                                    </div>
                                                @else
                                                    <div class="row">
                                                        <div class="col-xl-6 col-lg-6 form-group">
                                                            @include('admin.components.form.input',[
                                                                'label'         => __("Min Character*"),
                                                                'name'          => "min_char[]",
                                                                'type'          => "number",
                                                                'attribute'     => "required",
                                                                'value'         => old('min_char[]',$item->validation->min),
                                                                'placeholder'   => __("ex").": 6",
                                                            ])
                                                        </div>
                                                        <div class="col-xl-6 col-lg-6 form-group">
                                                            @include('admin.components.form.input',[
                                                                'label'         => __("Max Character*"),
                                                                'name'          => "max_char[]",
                                                                'type'          => "number",
                                                                'attribute'     => "required",
                                                                'value'         => old('max_char[]',$item->validation->max),
                                                                'placeholder'   => __("ex").": 16",
                                                            ])
                                                        </div>
                                                    </div>
                                                @endif
                                            </div>

                                            <div class="col-xl-2 col-lg-2 form-group">
                                                @include('admin.components.form.switcher',[
                                                    'label'     => __("Field Necessity*"),
                                                    'name'      => "field_necessity[]",
                                                    'options'   => ['Required' => 1,'Optional' => 0],
                                                    'value'     => old("field_necessity[]",$item->required),
                                                ])
                                            </div>
                                            <div class="col-xl-1 col-lg-1 form-group">
                                                <button type="button" class="custom-btn btn--base btn--danger row-cross-btn w-100 btn-loading"><i class="las la-times"></i></button>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="row mb-10-none">
                    <div class="col-xl-12 col-lg-12 form-group">
                        @include('admin.components.button.form-btn',[
                            'class'         => "w-100",
                            'text'          => __("update"),
                            'permission'    => "admin.payment.gateway.update",
                        ])
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@push('script')

@endpush
