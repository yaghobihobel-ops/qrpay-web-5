<div class="modal fade" id="thresholdEdit-{{ $threshold->id }}" tabindex="-1" aria-labelledby="thresholdEditLabel-{{ $threshold->id }}" aria-hidden="true">
    <div class="modal-dialog modal-md modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="thresholdEditLabel-{{ $threshold->id }}">{{ __('Edit Threshold') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form action="{{ setRoute('admin.risk.thresholds.update', $threshold->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Metric') }}</label>
                            <input type="text" name="metric" class="form-control" value="{{ old('metric', $threshold->metric) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Comparator') }}</label>
                            @php($selectedComparator = old('comparator', $threshold->comparator))
                            <select name="comparator" class="form-control" required>
                                <option value="gte" @selected($selectedComparator === 'gte')>{{ __('Greater than or equal') }}</option>
                                <option value="gt" @selected($selectedComparator === 'gt')>{{ __('Greater than') }}</option>
                                <option value="lte" @selected($selectedComparator === 'lte')>{{ __('Less than or equal') }}</option>
                                <option value="lt" @selected($selectedComparator === 'lt')>{{ __('Less than') }}</option>
                                <option value="eq" @selected($selectedComparator === 'eq')>{{ __('Equal') }}</option>
                                <option value="neq" @selected($selectedComparator === 'neq')>{{ __('Not equal') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Value') }}</label>
                            <input type="number" step="0.0001" name="value" class="form-control" value="{{ old('value', $threshold->value) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Decision') }}</label>
                            @php($selectedDecision = old('decision', $threshold->decision))
                            <select name="decision" class="form-control" required>
                                <option value="approve" @selected($selectedDecision === 'approve')>{{ __('Approve') }}</option>
                                <option value="manual_review" @selected($selectedDecision === 'manual_review')>{{ __('Manual Review') }}</option>
                                <option value="reject" @selected($selectedDecision === 'reject')>{{ __('Reject') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Priority') }}</label>
                            <input type="number" min="0" name="priority" class="form-control" value="{{ old('priority', $threshold->priority) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Description') }} <span class="text-muted">{{ __('(optional)') }}</span></label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $threshold->description) }}">
                        </div>
                        <div class="col-12">
                            @php($isActive = old('is_active', $threshold->is_active))
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="threshold-is-active-{{ $threshold->id }}" value="1" @checked($isActive)>
                                <label class="form-check-label" for="threshold-is-active-{{ $threshold->id }}">{{ __('Threshold is active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--sm btn--dark" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn--sm btn--primary">{{ __('Save Changes') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
