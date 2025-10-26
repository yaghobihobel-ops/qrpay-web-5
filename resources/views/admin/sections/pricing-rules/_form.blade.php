<div class="row g-3">
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">{{ __('Rule Name') }}</label>
            <input type="text" name="name" class="form-control" value="{{ old('name', $rule->name) }}" required>
        </div>
    </div>
    <div class="col-md-6">
        <div class="form-group">
            <label class="form-label">{{ __('Provider') }}</label>
            <input type="text" name="provider" class="form-control" value="{{ old('provider', $rule->provider) }}" placeholder="{{ __('Leave empty to apply to any provider') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('Currency') }}</label>
            <input type="text" name="currency" class="form-control" value="{{ old('currency', $rule->currency) }}" placeholder="{{ __('Use * or leave empty for any') }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('Transaction Type') }}</label>
            <input type="text" name="transaction_type" class="form-control" value="{{ old('transaction_type', $rule->transaction_type) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('User Level') }}</label>
            <input type="text" name="user_level" class="form-control" value="{{ old('user_level', $rule->user_level ?? 'standard') }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('Base Currency') }}</label>
            <input type="text" name="base_currency" class="form-control" value="{{ old('base_currency', $rule->base_currency ?? get_default_currency_code()) }}" required>
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('Rate Provider (optional)') }}</label>
            <input type="text" name="rate_provider" class="form-control" value="{{ old('rate_provider', $rule->rate_provider) }}">
        </div>
    </div>
    <div class="col-md-4">
        <div class="form-group">
            <label class="form-label">{{ __('Spread (bps)') }}</label>
            <input type="number" step="0.01" name="spread_bps" class="form-control" value="{{ old('spread_bps', $rule->spread_bps ?? 0) }}">
        </div>
    </div>
    <div class="col-md-12">
        <div class="form-group">
            <label class="form-label">{{ __('Status') }}</label>
            <select name="status" class="form-control" required>
                <option value="1" {{ old('status', $rule->status ?? true) ? 'selected' : '' }}>{{ __('Active') }}</option>
                <option value="0" {{ old('status', $rule->status ?? true) ? '' : 'selected' }}>{{ __('Disabled') }}</option>
            </select>
        </div>
    </div>
</div>
<hr>
<div class="mb-3 d-flex justify-content-between align-items-center">
    <h5 class="mb-0">{{ __('Fee Tiers') }}</h5>
    <button type="button" class="btn btn-sm btn--base" id="add-tier">{{ __('Add Tier') }}</button>
</div>
<div id="tiers-wrapper">
    @foreach(old('tiers', $tiers->toArray()) as $index => $tier)
    <div class="card border shadow-sm mb-3 tier-item">
        <div class="card-body">
            <div class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label class="form-label">{{ __('Min Amount') }}</label>
                    <input type="number" step="0.00000001" name="tiers[{{ $index }}][min_amount]" class="form-control" value="{{ $tier['min_amount'] ?? 0 }}" required>
                </div>
                <div class="col-md-3">
                    <label class="form-label">{{ __('Max Amount') }}</label>
                    <input type="number" step="0.00000001" name="tiers[{{ $index }}][max_amount]" class="form-control" value="{{ $tier['max_amount'] ?? '' }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Percent Fee (%)') }}</label>
                    <input type="number" step="0.0001" name="tiers[{{ $index }}][percent_fee]" class="form-control" value="{{ $tier['percent_fee'] ?? 0 }}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">{{ __('Fixed Fee') }}</label>
                    <input type="number" step="0.00000001" name="tiers[{{ $index }}][fixed_fee]" class="form-control" value="{{ $tier['fixed_fee'] ?? 0 }}">
                </div>
                <div class="col-md-1">
                    <label class="form-label">{{ __('Priority') }}</label>
                    <input type="number" name="tiers[{{ $index }}][priority]" class="form-control" value="{{ $tier['priority'] ?? 0 }}">
                </div>
                <div class="col-md-1 text-end">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-tier"><i class="las la-times"></i></button>
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@once
@push('script')
<script>
    (function(){
        const wrapper = document.getElementById('tiers-wrapper');
        const addBtn = document.getElementById('add-tier');
        if(!wrapper || !addBtn){
            return;
        }
        addBtn.addEventListener('click', function(){
            const index = wrapper.querySelectorAll('.tier-item').length;
            const template = `
                <div class="card border shadow-sm mb-3 tier-item">
                    <div class="card-body">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Min Amount') }}</label>
                                <input type="number" step="0.00000001" name="tiers[${index}][min_amount]" class="form-control" value="0" required>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label">{{ __('Max Amount') }}</label>
                                <input type="number" step="0.00000001" name="tiers[${index}][max_amount]" class="form-control">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Percent Fee (%)') }}</label>
                                <input type="number" step="0.0001" name="tiers[${index}][percent_fee]" class="form-control" value="0">
                            </div>
                            <div class="col-md-2">
                                <label class="form-label">{{ __('Fixed Fee') }}</label>
                                <input type="number" step="0.00000001" name="tiers[${index}][fixed_fee]" class="form-control" value="0">
                            </div>
                            <div class="col-md-1">
                                <label class="form-label">{{ __('Priority') }}</label>
                                <input type="number" name="tiers[${index}][priority]" class="form-control" value="0">
                            </div>
                            <div class="col-md-1 text-end">
                                <button type="button" class="btn btn-sm btn-outline-danger remove-tier"><i class="las la-times"></i></button>
                            </div>
                        </div>
                    </div>
                </div>`;
            wrapper.insertAdjacentHTML('beforeend', template);
        });

        wrapper.addEventListener('click', function(event){
            if(event.target.closest('.remove-tier')){
                const tier = event.target.closest('.tier-item');
                if(tier && wrapper.querySelectorAll('.tier-item').length > 1){
                    tier.remove();
                }
            }
        });
    })();
</script>
@endpush
@endonce
