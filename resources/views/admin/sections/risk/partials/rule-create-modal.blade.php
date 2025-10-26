<div class="modal fade" id="riskRuleCreate" tabindex="-1" aria-labelledby="riskRuleCreateLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riskRuleCreateLabel">{{ __('Create Risk Rule') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form action="{{ setRoute('admin.risk.rules.store') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Rule Name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Event Type') }}</label>
                            <select name="event_type" class="form-control" required>
                                @php
                                    $events = ['any' => __('Any'), 'ADD-MONEY' => __('Add Money'), 'MONEY-OUT' => __('Money Out'), 'WITHDRAW' => __('Withdraw'), 'MONEY-EXCHANGE' => __('Exchange'), 'REMITTANCE' => __('Remittance'), 'MAKE-PAYMENT' => __('Make Payment'), 'MERCHANT-PAYMENT' => __('Merchant Payment')];
                                @endphp
                                @foreach ($events as $value => $label)
                                    <option value="{{ $value }}" @selected(old('event_type') === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Match Type') }}</label>
                            <select name="match_type" class="form-control" required>
                                <option value="all" @selected(old('match_type', 'all') === 'all')>{{ __('All conditions') }}</option>
                                <option value="any" @selected(old('match_type') === 'any')>{{ __('Any condition') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Decision Action') }}</label>
                            <select name="action" class="form-control" required>
                                <option value="approve" @selected(old('action') === 'approve')>{{ __('Approve') }}</option>
                                <option value="manual_review" @selected(old('action') === 'manual_review')>{{ __('Manual Review') }}</option>
                                <option value="reject" @selected(old('action') === 'reject')>{{ __('Reject') }}</option>
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
                            <label class="form-label">{{ __('Conditions (JSON array)') }}</label>
                            <textarea name="conditions" class="form-control" rows="6" placeholder='[{"field":"transaction.payable","operator":"gte","value":1000}]' required>{{ old('conditions', '') }}</textarea>
                            <p class="risk-json-hint">{{ __('Define conditions as JSON. Each condition requires a field, operator (eq, neq, gt, gte, lt, lte, in, contains) and value.') }}</p>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="stop_on_match" id="rule-stop-on-match" value="1" @checked(old('stop_on_match', true))>
                                <label class="form-check-label" for="rule-stop-on-match">{{ __('Stop processing after match') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="rule-is-active" value="1" @checked(old('is_active', true))>
                                <label class="form-check-label" for="rule-is-active">{{ __('Rule is active') }}</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn--sm btn--dark" data-bs-dismiss="modal">{{ __('Close') }}</button>
                    <button type="submit" class="btn btn--sm btn--primary">{{ __('Save Rule') }}</button>
                </div>
            </form>
        </div>
    </div>
</div>
