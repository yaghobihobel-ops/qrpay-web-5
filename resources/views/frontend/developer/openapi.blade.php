@extends('frontend.layouts.developer_master')

@section('content')
<div class="developer-body-wrapper">
    <div class="developer-main-wrapper">
        <h1 class="heading-title mb-20">{{ __("OpenAPI & SDKs") }}</h1>
        <p>{{ __("The QRPay API is fully described with an OpenAPI 3.1 specification, enabling automatic client generation and up-to-date documentation.") }}</p>
        <div class="mt-20">
            <h3 class="heading-title h5">{{ __("Download the specification") }}</h3>
            <ul class="unordered-list mt-10">
                <li><a class="text-decoration-underline" href="{{ asset('docs/openapi/qrpay-api-1.0.0.yaml') }}" download>qrpay-api-1.0.0.yaml</a> — {{ __("Versioned schema with request/response examples") }}</li>
                <li>{{ __("Changelog available in the admin portal (Developer → API Changelog)") }}</li>
            </ul>
        </div>
        <div class="mt-30">
            <h3 class="heading-title h5">{{ __("SDK availability") }}</h3>
            <p>{{ __("SDKs are generated from the specification using OpenAPI Generator and published to GitHub Packages with semantic versioning.") }}</p>
            <div class="table-responsive">
                <table class="table table-striped">
                    <thead>
                        <tr>
                            <th>{{ __("Language") }}</th>
                            <th>{{ __("Package") }}</th>
                            <th>{{ __("Install") }}</th>
                            <th>{{ __("Docs") }}</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>TypeScript</td>
                            <td><code>@qrpay/sdk</code></td>
                            <td><code>npm install @qrpay/sdk</code></td>
                            <td><a href="https://github.com/qrpay/ts-sdk/packages/qrpay-sdk" target="_blank" rel="noopener">GitHub Packages</a></td>
                        </tr>
                        <tr>
                            <td>Python</td>
                            <td><code>qrpay-sdk</code></td>
                            <td><code>pip install qrpay-sdk --extra-index-url https://pip.pkg.github.com/qrpay/simple/</code></td>
                            <td><a href="https://github.com/qrpay/python-sdk/packages/qrpay-sdk" target="_blank" rel="noopener">GitHub Packages</a></td>
                        </tr>
                        <tr>
                            <td>PHP</td>
                            <td><code>qrpay/sdk</code></td>
                            <td><code>composer require qrpay/sdk</code></td>
                            <td><a href="https://github.com/qrpay/php-sdk/packages/qrpay-sdk" target="_blank" rel="noopener">GitHub Packages</a></td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="mt-30">
            <h3 class="heading-title h5">{{ __("Generate your own SDK") }}</h3>
            <p>{{ __("Use the provided automation script to reproduce the official SDKs or create language-specific forks.") }}</p>
            <pre><code class="language-bash">git clone git@github.com:qrpay/qrpay-web.git
cd qrpay-web
./sdk/generate.sh
</code></pre>
            <p>{{ __("Customize the generator configurations in `sdk/openapi-generator-*.yaml` to change namespaces or packaging options.") }}</p>
        </div>
    </div>
    <div class="page-change-area">
        <div class="navigation-wrapper">
            <a href="{{ setRoute('developer.sandbox') }}" class="left"><i class="las la-arrow-left me-1"></i> {{ __("Sandbox Environment") }}</a>
            <a href="{{ setRoute('developer.postman') }}" class="right">{{ __("Postman Collection") }} <i class="las la-arrow-right ms-1"></i></a>
        </div>
    </div>
</div>
@endsection

@push('script')
@endpush
