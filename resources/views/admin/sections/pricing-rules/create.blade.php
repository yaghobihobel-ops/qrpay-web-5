@extends('admin.layouts.master')

@section('content')
<div class="body-wrapper">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Create pricing rule') }}</h4>
            <a href="{{ route('admin.pricing-rules.index') }}" class="btn btn--sm btn--dark">{{ __('Back to list') }}</a>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.pricing-rules.store') }}" method="POST">
                @csrf
                @include('admin.sections.pricing-rules.partials.form', ['rule' => $rule])
                <div class="mt-4 text-end">
                    <button type="submit" class="btn btn--primary">{{ __('Save rule') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
