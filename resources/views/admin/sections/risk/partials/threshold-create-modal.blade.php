<div class="modal fade" id="thresholdCreate" tabindex="-1" aria-labelledby="thresholdCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="thresholdCreateLabel">{{ __('Create Threshold') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form action="{{ setRoute('admin.risk.thresholds.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Metric') }}</label>
                            <input type="text" name="metric" class="form-control" value="{{ old('metric') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Comparator') }}</label>
                            <select name="comparator" class="form-control" required>
                                <option value="gte" @selected(old('comparator', 'gte') === 'gte')>{{ __('Greater than or equal') }}</option>
                                <option value="gt" @selected(old('comparator') === 'gt')>{{ __('Greater than') }}</option>
                                <option value="lte" @selected(old('comparator') === 'lte')>{{ __('Less than or equal') }}</option>
                                <option value="lt" @selected(old('comparator') === 'lt')>{{ __('Less than') }}</option>
                                <option value="eq" @selected(old('comparator') === 'eq')>{{ __('Equal') }}</option>
                                <option value="neq" @selected(old('comparator') === 'neq')>{{ __('Not equal') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Value') }}</label>
                            <input type="number" step="0.0001" name="value" class="form-control" value="{{ old('value') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Decision') }}</label>
                            <select name="decision" class="form-control" required>
                                <option value="approve" @selected(old('decision') === 'approve')>{{ __('Approve') }}</option>
                                <option value="manual_review" @selected(old('decision') === 'manual_review')>{{ __('Manual Review') }}</option>
                                <option value="reject" @selected(old('decision') === 'reject')>{{ __('Reject') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Priority') }}</label>
                            <input type="number" min="0" name="priority" class="form-control" value="{{ old('priority', 100) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Description') }} <span class="text-muted">{{ __('(optional)') }}</span></label>
                            <input type="text" name="description" class="form-control" value="{{ old('description') }}">
                        </div>
                        <div class="col-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="threshold-is-active" value="1" @checked(old('is_active', true))>
                                <label class="form-check-label" for="threshold-is-active">{{ __('Threshold is active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--sm btn--dark" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn--sm btn--primary">{{ __('Save Threshold') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
