<div class="modal fade" id="riskRuleEdit-{{ $rule->id }}" tabindex="-1" aria-labelledby="riskRuleEditLabel-{{ $rule->id }}" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="riskRuleEditLabel-{{ $rule->id }}">{{ __('Edit Risk Rule') }}</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="{{ __('Close') }}"></button>
            </div>
            <form action="{{ setRoute('admin.risk.rules.update', $rule->id) }}" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Rule Name') }}</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name', $rule->name) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Event Type') }}</label>
                            <select name="event_type" class="form-control" required>
                                @php
                                    $events = ['any' => __('Any'), 'ADD-MONEY' => __('Add Money'), 'MONEY-OUT' => __('Money Out'), 'WITHDRAW' => __('Withdraw'), 'MONEY-EXCHANGE' => __('Exchange'), 'REMITTANCE' => __('Remittance'), 'MAKE-PAYMENT' => __('Make Payment'), 'MERCHANT-PAYMENT' => __('Merchant Payment')];
                                    $selectedEvent = old('event_type', $rule->event_type);
                                @endphp
                                @foreach ($events as $value => $label)
                                    <option value="{{ $value }}" @selected($selectedEvent === $value)>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Match Type') }}</label>
                            @php($selectedMatch = old('match_type', $rule->match_type))
                            <select name="match_type" class="form-control" required>
                                <option value="all" @selected($selectedMatch === 'all')>{{ __('All conditions') }}</option>
                                <option value="any" @selected($selectedMatch === 'any')>{{ __('Any condition') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Decision Action') }}</label>
                            @php($selectedAction = old('action', $rule->action))
                            <select name="action" class="form-control" required>
                                <option value="approve" @selected($selectedAction === 'approve')>{{ __('Approve') }}</option>
                                <option value="manual_review" @selected($selectedAction === 'manual_review')>{{ __('Manual Review') }}</option>
                                <option value="reject" @selected($selectedAction === 'reject')>{{ __('Reject') }}</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Priority') }}</label>
                            <input type="number" min="0" name="priority" class="form-control" value="{{ old('priority', $rule->priority) }}" required>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label">{{ __('Description') }} <span class="text-muted">{{ __('(optional)') }}</span></label>
                            <input type="text" name="description" class="form-control" value="{{ old('description', $rule->description) }}">
                        </div>
                        <div class="col-12">
                            <label class="form-label">{{ __('Conditions (JSON array)') }}</label>
                            <textarea name="conditions" class="form-control" rows="6" required>{{ old('conditions', json_encode($rule->conditions, JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES)) }}</textarea>
                            <p class="risk-json-hint">{{ __('Define conditions as JSON. Each condition requires a field, operator (eq, neq, gt, gte, lt, lte, in, contains) and value.') }}</p>
                        </div>
                        <div class="col-md-6">
                            @php($stop = old('stop_on_match', $rule->stop_on_match))
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="stop_on_match" id="rule-stop-on-match-{{ $rule->id }}" value="1" @checked($stop)>
                                <label class="form-check-label" for="rule-stop-on-match-{{ $rule->id }}">{{ __('Stop processing after match') }}</label>
                            </div>
                        </div>
                        <div class="col-md-6">
                            @php($active = old('is_active', $rule->is_active))
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="is_active" id="rule-is-active-{{ $rule->id }}" value="1" @checked($active)>
                                <label class="form-check-label" for="rule-is-active-{{ $rule->id }}">{{ __('Rule is active') }}</label>
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
