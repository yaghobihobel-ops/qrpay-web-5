@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __("Dashboard"),
            'url'   => setRoute("admin.dashboard"),
        ],
        [
            'name'  => __('Pricing Rules'),
            'url'   => setRoute('admin.pricing.rules.index'),
        ]
    ], 'active' => __($page_title)])
@endsection

@section('content')
<div class="card">
    <div class="card-body">
        <form action="{{ setRoute('admin.pricing.rules.update', $rule) }}" method="POST">
            @csrf
            @method('PUT')
            @include('admin.sections.pricing-rules._form')
            <div class="text-end">
                <button type="submit" class="btn--base">{{ __('Update Rule') }}</button>
            </div>
        </form>
    </div>
</div>
@endsection
