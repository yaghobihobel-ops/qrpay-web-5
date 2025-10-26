@extends('admin.layouts.master')

@push('css')
<style>
    .api-help-page .api-video-card iframe {
        width: 100%;
        height: 100%;
        min-height: 260px;
        border: 0;
        border-radius: 12px;
    }
    .api-help-page .api-video-wrapper {
        flex: 1 1 420px;
        max-width: 540px;
    }
    .api-help-page .api-video-content {
        flex: 1 1 320px;
    }
    .api-help-search .input-group-text {
        background: transparent;
        border-right: 0;
    }
    .api-help-search .form-control {
        border-left: 0;
    }
    .api-help-search .form-control:focus {
        box-shadow: none;
    }
    .api-category-icon {
        width: 52px;
        height: 52px;
        border-radius: 14px;
        background: rgba(90, 115, 255, 0.12);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 24px;
        color: var(--base, #5a73ff);
    }
    .api-endpoints-table th,
    .api-endpoints-table td {
        white-space: nowrap;
    }
    .api-endpoints-table td:last-child {
        white-space: normal;
    }
    .api-help-page .accordion-button:not(.collapsed) {
        color: var(--base, #5a73ff);
        background-color: rgba(90, 115, 255, 0.12);
    }
    @media (max-width: 991px) {
        .api-help-page .api-video-wrapper,
        .api-help-page .api-video-content {
            flex: 1 1 100%;
            max-width: 100%;
        }
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
        'active' => __('API Guide'),
    ])
@endsection

@section('content')
    <div class="api-help-page">
        <div class="card api-video-card mb-15">
            <div class="card-body">
                <div class="d-flex flex-wrap gap-4 align-items-start">
                    <div class="api-video-content">
                        <span class="badge badge--success mb-2">{{ __('Welcome to QRPay APIs') }}</span>
                        <h4 class="mb-3">{{ __('Get started with the API suite') }}</h4>
                        <p class="text-muted mb-4">{{ __('Watch the onboarding video and search the curated documentation to accelerate your integration journey.') }}</p>
                        <div class="api-help-search">
                            <label class="form-label fw-semibold">{{ __('Search the API content') }}</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="las la-search"></i></span>
                                <input type="search" class="form-control" name="q" value="{{ $searchTerm }}" placeholder="{{ __('Search by keyword, endpoint or FAQ...') }}" data-api-help-search dusk="api-help-search">
                            </div>
                            <small class="text-muted d-block mt-2">{{ __('Tip: try “token refresh” or “payout fees”.') }}</small>
                        </div>
                    </div>
                    <div class="api-video-wrapper ratio ratio-16x9">
                        <iframe src="{{ $videoUrl }}" title="{{ __('API overview video') }}" loading="lazy" allowfullscreen></iframe>
                    </div>
                </div>
            </div>
        </div>

        <div class="card mb-15">
            <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                <div>
                    <h5 class="mb-1">{{ __('Postman collection') }}</h5>
                    <p class="text-muted mb-0">{{ __('Open the curated Postman workspace to explore every endpoint with ready-made examples.') }}</p>
                </div>
                <a href="{{ $postmanCollectionUrl }}" target="_blank" class="btn--base" dusk="api-help-postman-link">{{ __('Open collection') }}</a>
            </div>
        </div>

        <div class="row g-3 api-help-categories" data-api-category-wrapper>
            @forelse($categories as $category)
                <div class="col-12" data-api-category data-keywords="{{ $category['keywords'] }}" dusk="api-category-{{ $category['slug'] }}">
                    <div class="card h-100">
                        <div class="card-header bg-transparent border-0 pb-0">
                            <div class="d-flex gap-3 flex-wrap align-items-start">
                                <span class="api-category-icon"><i class="{{ $category['icon'] }}"></i></span>
                                <div>
                                    <h5 class="mb-1">{{ __($category['title']) }}</h5>
                                    <p class="text-muted mb-0">{{ __($category['description']) }}</p>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="text-uppercase fw-semibold mb-2">{{ __('Key endpoints') }}</h6>
                            <div class="table-responsive mb-4">
                                <table class="table table-borderless table-sm align-middle api-endpoints-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ __('Method') }}</th>
                                            <th>{{ __('Endpoint') }}</th>
                                            <th>{{ __('Description') }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($category['endpoints'] as $endpoint)
                                            <tr>
                                                <td><span class="badge badge--primary">{{ $endpoint['method'] }}</span></td>
                                                <td><code>{{ $endpoint['path'] }}</code></td>
                                                <td>{{ __($endpoint['description']) }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                            <h6 class="text-uppercase fw-semibold mb-2">{{ __('Frequently asked questions') }}</h6>
                            <div class="accordion api-faq" id="faq-{{ $category['slug'] }}">
                                @foreach($category['matched_faqs'] as $index => $faq)
                                    @php($collapseId = 'faq-'.$category['slug'].'-'.$index)
                                    <div class="accordion-item" dusk="api-faq-{{ $category['slug'] }}-{{ $index }}">
                                        <h2 class="accordion-header" id="{{ $collapseId }}-header">
                                            <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#{{ $collapseId }}" aria-expanded="false" aria-controls="{{ $collapseId }}">
                                                {{ __($faq['question']) }}
                                            </button>
                                        </h2>
                                        <div id="{{ $collapseId }}" class="accordion-collapse collapse" aria-labelledby="{{ $collapseId }}-header" data-bs-parent="#faq-{{ $category['slug'] }}">
                                            <div class="accordion-body">
                                                {{ __($faq['answer']) }}
                                            </div>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12" data-api-no-result dusk="api-help-no-results">
                    <div class="card border-0 shadow-none text-center">
                        <div class="card-body py-5">
                            <i class="las la-search fs-1 text-muted mb-3"></i>
                            <h5 class="mb-1">{{ __('No content found for your search') }}</h5>
                            <p class="text-muted mb-0">{{ __('Try adjusting your query or clear the search input to see all API guides again.') }}</p>
                        </div>
                    </div>
                </div>
            @endforelse
            <div class="col-12 d-none" data-api-no-result dusk="api-help-no-results-empty">
                <div class="card border-0 shadow-none text-center">
                    <div class="card-body py-5">
                        <i class="las la-search fs-1 text-muted mb-3"></i>
                        <h5 class="mb-1">{{ __('No content found for your search') }}</h5>
                        <p class="text-muted mb-0">{{ __('Try adjusting your query or clear the search input to see all API guides again.') }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@push('script')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-api-help-search]');
        if (!searchInput) {
            return;
        }

        const categories = Array.from(document.querySelectorAll('[data-api-category]'));
        const emptyState = document.querySelector('[data-api-no-result].d-none');

        if (!categories.length) {
            return;
        }

        const toggleVisibility = (value) => {
            const query = value.trim().toLowerCase();
            let visibleCount = 0;

            categories.forEach((category) => {
                const keywords = (category.getAttribute('data-keywords') || '').toLowerCase();
                const shouldShow = !query || keywords.includes(query);

                category.classList.toggle('d-none', !shouldShow);
                if (shouldShow) {
                    visibleCount++;
                }
            });

            if (emptyState) {
                emptyState.classList.toggle('d-none', visibleCount !== 0);
            }
        };

        toggleVisibility(searchInput.value || '');

        searchInput.addEventListener('input', function (event) {
            toggleVisibility(event.target.value);
        });
    });
</script>
@endpush
