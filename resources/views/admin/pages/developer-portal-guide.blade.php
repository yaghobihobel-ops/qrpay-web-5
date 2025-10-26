@extends('admin.layouts.master')

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/github-markdown-css@5.2.0/github-markdown-light.min.css">
    <style>
        .markdown-body h1:first-child {
            margin-top: 0;
        }
    </style>
@endpush

@section('page-title')
    @include('admin.components.page-title', ['title' => __('Developer Portal Guide')])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb', [
        'breadcrumbs' => [
            [
                'name' => __('Dashboard'),
                'url' => setRoute('admin.dashboard'),
            ],
        ],
        'active' => __('Developer Portal Guide'),
    ])
@endsection

@section('content')
    <div class="row gy-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex flex-wrap justify-content-between align-items-center gap-3">
                    <h4 class="mb-0">{{ __('Developer Portal Guide') }}</h4>
                    @if($updatedAt)
                        <span class="text-muted small">{{ __('Last updated: :date', ['date' => $updatedAt]) }}</span>
                    @endif
                </div>
                <div class="card-body">
                    <div class="markdown-body">
                        {!! $document !!}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const container = document.querySelector('.markdown-body');
            if (container && container.textContent.trim() === '') {
                container.innerHTML = '<p>{{ addslashes(__('The developer portal guide is currently unavailable.')) }}</p>';
            }
        });
    </script>
@endpush
