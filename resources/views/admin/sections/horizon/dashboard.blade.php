@extends('admin.layouts.master')

@section('page-title')
    <h4 class="title">{{ __('Queue Monitor') }}</h4>
@endsection

@section('content')
    <div class="card">
        <div class="card-body">
            @if(!$horizonAvailable)
                <div class="alert alert-warning mb-0">
                    {{ __('Laravel Horizon is not installed. Please install the package before attempting to view the dashboard.') }}
                </div>
            @else
                <iframe
                    src="{{ $horizonPath }}"
                    title="{{ __('Horizon Dashboard') }}"
                    style="width: 100%; min-height: 80vh; border: 0;"
                    loading="lazy"
                ></iframe>
            @endif
        </div>
    </div>
@endsection
