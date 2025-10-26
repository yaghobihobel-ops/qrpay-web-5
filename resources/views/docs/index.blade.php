@extends('frontend.layouts.master')

@php($page_title = __('API Documentation'))

@section('content')
    <section class="pt-120 pb-120 bg--light">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-10">
                    <div class="card border-0 shadow-sm">
                        <div class="card-body p-0">
                            <div id="redoc-container" class="markdown-body p-4" style="min-height: 70vh;">
                                <noscript>{{ __('JavaScript is required to render the interactive API reference.') }}</noscript>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection

@push('css')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/github-markdown-css@5.2.0/github-markdown-light.min.css">
@endpush

@push('script')
    <script src="https://cdn.redoc.ly/redoc/latest/bundles/redoc.standalone.js" integrity="sha384-Q9jOQgpdnH92lhw/Bod9YfvT8wT0W3ULb53ZBLnxmy63O+bx5NtBXFwhCClrZj6b" crossorigin="anonymous"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            Redoc.init("{{ asset('public/docs/openapi/v2/openapi.yaml') }}", {
                hideDownloadButton: false,
                expandResponses: "200,201",
            }, document.getElementById('redoc-container'));
        });
    </script>
@endpush
