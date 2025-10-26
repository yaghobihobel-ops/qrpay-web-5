@extends('admin.layouts.master')

@push('css')
    <style>
        .documentation-quick-links .btn {
            margin-bottom: 0.5rem;
            width: 100%;
            text-align: left;
        }
        .documentation-content h2,
        .documentation-content h3,
        .documentation-content h4 {
            margin-top: 2rem;
        }
        .documentation-content table {
            width: 100%;
        }
        .documentation-content table th,
        .documentation-content table td {
            text-align: right;
            padding: 0.75rem;
        }
        .documentation-content blockquote {
            border-right: 4px solid var(--base);
            padding: 0.75rem 1rem;
            background: rgba(0,0,0,0.03);
        }
    </style>
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
        'active' => __('راهنمای توسعه')
    ])
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-xl-3">
            <div class="card h-100">
                <div class="card-body documentation-quick-links">
                    <h5 class="card-title mb-3">{{ __('دسترسی سریع') }}</h5>
                    @foreach ($quickLinks as $anchor => $label)
                        <a class="btn btn--base-outline" href="#{{ $anchor }}">
                            <i class="las la-link me-2"></i>{{ $label }}
                        </a>
                    @endforeach
                    <hr>
                    <h6 class="text-muted">{{ __('راهنماهای نسخه‌بندی شده') }}</h6>
                    <ul class="list-unstyled mt-2 mb-0">
                        <li><a href="#نسخه-۱۱" class="text--base">{{ __('تعریف قراردادها v1.1') }}</a></li>
                        <li><a href="#نسخه-۱۲" class="text--base">{{ __('ساختار سرویس‌ها v1.2') }}</a></li>
                        <li><a href="#نسخه-۱۰" class="text--base">{{ __('پایش v1.0') }}</a></li>
                    </ul>
                </div>
            </div>
        </div>
        <div class="col-xl-9">
            <div class="card">
                <div class="card-body documentation-content">
                    {!! $documentation !!}
                </div>
            </div>
        </div>
    </div>
@endsection
