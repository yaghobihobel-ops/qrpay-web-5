@extends('admin.layouts.master')

@section('page-title')
    @include('admin.components.page-title',['title' => __($page_title)])
@endsection

@section('breadcrumb')
    @include('admin.components.breadcrumb',['breadcrumbs' => [
        [
            'name'  => __('Dashboard'),
            'url'   => setRoute('admin.dashboard'),
        ]
    ], 'active' => __($page_title)])
@endsection

@section('content')
<div class="body-wrapper">
    <div class="row mb-none-30">
        <div class="col-xl-3 col-sm-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Total views') }}</span>
                    <h3 class="mt-2">{{ number_format($totalViews) }}</h3>
                    <p class="mb-0 text-muted">{{ __('All time help center views recorded.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Average read time (s)') }}</span>
                    <h3 class="mt-2">{{ number_format($averageRead, 2) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Average seconds users spent in articles.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Unique viewers') }}</span>
                    <h3 class="mt-2">{{ number_format($uniqueViewers) }}</h3>
                    <p class="mb-0 text-muted">{{ __('Based on authenticated users or session ids.') }}</p>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-sm-6 mb-30">
            <div class="card custom-card h-100">
                <div class="card-body">
                    <span class="text-uppercase text-muted small">{{ __('Top language') }}</span>
                    <h3 class="mt-2">{{ $topLanguage ? strtoupper($topLanguage) : __('N/A') }}</h3>
                    <p class="mb-0 text-muted">{{ __('Most frequently consumed locale.') }}</p>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30">
        <div class="col-xl-7 mb-30">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('Section performance') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Section') }}</th>
                                    <th>{{ __('Views') }}</th>
                                    <th>{{ __('Avg. read (s)') }}</th>
                                    <th>{{ __('Last viewed') }}</th>
                                    <th>{{ __('Languages') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($sectionStats as $item)
                                    <tr>
                                        <td>
                                            <strong>{{ $item['title'] }}</strong>
                                            @if($item['summary'])
                                                <div class="text-muted small">{{ $item['summary'] }}</div>
                                            @endif
                                        </td>
                                        <td>{{ number_format($item['views']) }}</td>
                                        <td>{{ number_format($item['avg_duration'], 2) }}</td>
                                        <td>{{ optional($item['last_viewed'])->format('M d, Y H:i') ?? __('Never') }}</td>
                                        <td>
                                            @foreach($item['languages'] as $lang)
                                                <span class="badge badge--info me-1">{{ strtoupper($lang) }}</span>
                                            @endforeach
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">{{ __('No views recorded yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-5 mb-30">
            <div class="card custom-card h-100">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('14 day trend') }}</h5>
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0 help-trend-list">
                        @foreach($trendSeries as $day)
                            <li class="d-flex align-items-center justify-content-between py-1 border-bottom">
                                <span>{{ $day['label'] }}</span>
                                <span class="fw-semibold">{{ $day['value'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="row mb-none-30">
        <div class="col-xl-12 mb-30">
            <div class="card custom-card">
                <div class="card-header d-flex align-items-center justify-content-between">
                    <h5 class="card-title mb-0">{{ __('Top FAQs') }}</h5>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-striped mb-0">
                            <thead>
                                <tr>
                                    <th>{{ __('Question') }}</th>
                                    <th>{{ __('Section') }}</th>
                                    <th>{{ __('Interactions') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($faqStats as $faq)
                                    <tr>
                                        <td>{{ $faq['question'] }}</td>
                                        <td><span class="badge badge--primary">{{ $faq['section_id'] }}</span></td>
                                        <td>{{ number_format($faq['total']) }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="3" class="text-center text-muted py-4">{{ __('No FAQ activity recorded yet.') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('css')
    <style>
        .help-trend-list li:last-child {
            border-bottom: none;
        }
        .help-trend-list li span:first-child {
            font-weight: 500;
        }
    </style>
@endpush
