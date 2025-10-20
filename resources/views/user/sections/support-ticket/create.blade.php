@extends('user.layouts.master')

@push('css')

@endpush

@section('breadcrumb')
    @include('user.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("user.dashboard"),
        ]
    ], 'active' => __("Support Tickets")])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="row mb-20-none">
        <div class="col-xl-12 col-lg-12 mb-20">
            <div class="custom-card mt-10">
                <div class="dashboard-header-wrapper">
                    <h4 class="title">{{ __("Add New Ticket") }}</h4>
                </div>
                <div class="card-body">
                    <form class="card-form" action="{{ route('user.support.ticket.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <div class="row">
                            <input type="hidden" name="name" value="{{userGuard()['user']->fullname??""}}">
                            <input type="hidden" name="email" value="{{userGuard()['user']->email??""}}">
                            <div class="col-xl-12 col-lg-12 form-group">
                                @include('admin.components.form.input',[
                                    'label'         => __("Subject")."<span>*</span>",
                                    'name'          => "subject",
                                    'placeholder'   => __("Enter Subject"),
                                ])
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                @include('admin.components.form.textarea',[
                                    'label'         => __("Message")."<span>*</span>",
                                    'name'          => "desc",
                                    'placeholder'   => __("Write Here.."),
                                ])
                            </div>
                            <div class="col-xl-12 col-lg-12 form-group">
                                <label>{{ __("Attachments") }}</label>
                                <input type="file" class="file-holder" name="attachment[]" id="fileUpload" data-height="130" accept="image/*" data-max_size="20" data-file_limit="15">
                            </div>
                        </div>
                        <div class="col-xl-12 col-lg-12">
                            <button type="submit" class="btn--base w-100">{{ __("Add New") }}</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('script')
    <script>

    </script>
@endpush
