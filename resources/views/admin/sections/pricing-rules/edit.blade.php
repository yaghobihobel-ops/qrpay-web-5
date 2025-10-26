@extends('admin.layouts.master')

@section('content')
<div class="body-wrapper">
    <div class="card">
        <div class="card-header d-flex align-items-center justify-content-between">
            <h4 class="card-title mb-0">{{ __('Edit pricing rule') }}</h4>
            <a href="{{ route('admin.pricing-rules.index') }}" class="btn btn--sm btn--dark">{{ __('Back to list') }}</a>
        </div>
        <div class="card-body">
            <form action="{{ route('admin.pricing-rules.update', $rule) }}" method="POST">
                @csrf
                @method('PUT')
                @include('admin.sections.pricing-rules.partials.form', ['rule' => $rule])
                <div class="mt-4 d-flex justify-content-between align-items-center">
                    <div>
                        <span class="text-muted small">{{ __('Last updated') }}: {{ $rule->updated_at?->format('Y-m-d H:i') }}</span>
                    </div>
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn--primary">{{ __('Update rule') }}</button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
