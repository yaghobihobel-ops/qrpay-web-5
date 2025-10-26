@php
    $tiers = old('tiers', isset($rule) ? $rule->feeTiers?->map(function ($tier) {
        return [
            'id' => $tier->id,
            'min_amount' => $tier->min_amount,
            'max_amount' => $tier->max_amount,
            'fee_type' => $tier->fee_type,
            'fee_amount' => $tier->fee_amount,
            'fee_currency' => $tier->fee_currency,
            'priority' => $tier->priority,
        ];
    })->toArray() : []);
    if (empty($tiers)) {
        $tiers = [[]];
    }
@endphp

<div class="row g-3">
    <div class="col-md-6">
        <label class="form-label">{{ __('Name') }}</label>
        <input type="text" class="form--control" name="name" value="{{ old('name', $rule->name ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Provider') }}</label>
        <input type="text" class="form--control" name="provider" value="{{ old('provider', $rule->provider ?? '') }}" required>
    </div>
    <div class="col-md-3">
        <label class="form-label">{{ __('Currency') }}</label>
        <input type="text" class="form--control" name="currency" value="{{ old('currency', $rule->currency ?? get_default_currency_code()) }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Transaction type') }}</label>
        <input type="text" class="form--control" name="transaction_type" value="{{ old('transaction_type', $rule->transaction_type ?? 'withdraw') }}" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('User level') }}</label>
        <input type="text" class="form--control" name="user_level" value="{{ old('user_level', $rule->user_level ?? '') }}" placeholder="{{ __('Example: verified, premium') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Priority') }}</label>
        <input type="number" class="form--control" name="priority" value="{{ old('priority', $rule->priority ?? 100) }}" min="0" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Fee type') }}</label>
        <select class="form--control" name="fee_type">
            @foreach(['percentage', 'flat', 'bps'] as $type)
                <option value="{{ $type }}" @selected(old('fee_type', $rule->fee_type ?? 'percentage') === $type)>{{ ucfirst($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Fee amount / rate') }}</label>
        <input type="number" step="0.00000001" class="form--control" name="fee_amount" value="{{ old('fee_amount', $rule->fee_amount ?? 0) }}" min="0" required>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Fee currency') }}</label>
        <input type="text" class="form--control" name="fee_currency" value="{{ old('fee_currency', $rule->fee_currency ?? $rule->currency ?? get_default_currency_code()) }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Minimum amount (optional)') }}</label>
        <input type="number" step="0.00000001" class="form--control" name="min_amount" value="{{ old('min_amount', $rule->min_amount ?? null) }}" min="0">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Maximum amount (optional)') }}</label>
        <input type="number" step="0.00000001" class="form--control" name="max_amount" value="{{ old('max_amount', $rule->max_amount ?? null) }}" min="0">
    </div>
    <div class="col-md-4 d-flex align-items-center gap-2">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" id="activeRule" name="active" value="1" @checked(old('active', $rule->active ?? true))>
            <label class="form-check-label" for="activeRule">{{ __('Active') }}</label>
        </div>
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Experiment key') }}</label>
        <input type="text" class="form--control" name="experiment" value="{{ old('experiment', $rule->experiment ?? '') }}" placeholder="{{ __('Optional experiment name') }}">
    </div>
    <div class="col-md-4">
        <label class="form-label">{{ __('Variant') }}</label>
        <input type="text" class="form--control" name="variant" value="{{ old('variant', $rule->variant ?? 'control') }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Starts at') }}</label>
        <input type="datetime-local" class="form--control" name="starts_at" value="{{ old('starts_at', optional($rule->starts_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-md-6">
        <label class="form-label">{{ __('Ends at') }}</label>
        <input type="datetime-local" class="form--control" name="ends_at" value="{{ old('ends_at', optional($rule->ends_at)->format('Y-m-d\TH:i')) }}">
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('Description') }}</label>
        <textarea name="metadata[description]" class="form--control" rows="2">{{ old('metadata.description', data_get($rule, 'metadata.description')) }}</textarea>
    </div>
    <div class="col-12">
        <label class="form-label">{{ __('Internal notes') }}</label>
        <textarea name="metadata[notes]" class="form--control" rows="2">{{ old('metadata.notes', data_get($rule, 'metadata.notes')) }}</textarea>
    </div>
</div>

<div class="card mt-4">
    <div class="card-header d-flex justify-content-between align-items-center">
        <h5 class="mb-0">{{ __('Fee tiers') }}</h5>
        <button class="btn btn--sm btn--success" type="button" data-add-tier="true"><i class="las la-plus me-1"></i>{{ __('Add tier') }}</button>
    </div>
    <div class="card-body">
        <div class="table-responsive">
            <table class="table table--responsive--md mb-0">
                <thead>
                    <tr>
                        <th>{{ __('Min amount') }}</th>
                        <th>{{ __('Max amount') }}</th>
                        <th>{{ __('Fee type') }}</th>
                        <th>{{ __('Fee amount') }}</th>
                        <th>{{ __('Fee currency') }}</th>
                        <th>{{ __('Priority') }}</th>
                        <th>{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody data-tier-wrapper="true">
                    @foreach($tiers as $index => $tier)
                        <tr data-tier-row="{{ $index }}">
                            <td><input type="number" step="0.00000001" name="tiers[{{ $index }}][min_amount]" class="form--control" value="{{ old("tiers.$index.min_amount", $tier['min_amount'] ?? null) }}" min="0"></td>
                            <td><input type="number" step="0.00000001" name="tiers[{{ $index }}][max_amount]" class="form--control" value="{{ old("tiers.$index.max_amount", $tier['max_amount'] ?? null) }}" min="0"></td>
                            <td>
                                <select name="tiers[{{ $index }}][fee_type]" class="form--control">
                                    @foreach(['percentage', 'flat', 'bps'] as $type)
                                        <option value="{{ $type }}" @selected(old("tiers.$index.fee_type", $tier['fee_type'] ?? 'percentage') === $type)>{{ ucfirst($type) }}</option>
                                    @endforeach
                                </select>
                            </td>
                            <td><input type="number" step="0.00000001" name="tiers[{{ $index }}][fee_amount]" class="form--control" value="{{ old("tiers.$index.fee_amount", $tier['fee_amount'] ?? null) }}" min="0"></td>
                            <td><input type="text" name="tiers[{{ $index }}][fee_currency]" class="form--control" value="{{ old("tiers.$index.fee_currency", $tier['fee_currency'] ?? null) }}" placeholder="{{ __('Same as rule currency') }}"></td>
                            <td><input type="number" name="tiers[{{ $index }}][priority]" class="form--control" value="{{ old("tiers.$index.priority", $tier['priority'] ?? 100) }}" min="0"></td>
                            <td class="text-end">
                                <button class="btn btn--danger btn--sm" type="button" data-remove-tier="true"><i class="las la-times"></i></button>
                                @if(!empty($tier['id']))
                                    <input type="hidden" name="tiers[{{ $index }}][id]" value="{{ $tier['id'] }}">
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>

@push('script')
<script>
    (function(){
        const tierWrapper = document.querySelector('[data-tier-wrapper]');
        const addButton = document.querySelector('[data-add-tier]');

        if (!tierWrapper || !addButton) {
            return;
        }

        addButton.addEventListener('click', function(){
            const index = tierWrapper.querySelectorAll('tr').length;
            const template = `
                <tr data-tier-row="${index}">
                    <td><input type="number" step="0.00000001" name="tiers[${index}][min_amount]" class="form--control" min="0"></td>
                    <td><input type="number" step="0.00000001" name="tiers[${index}][max_amount]" class="form--control" min="0"></td>
                    <td>
                        <select name="tiers[${index}][fee_type]" class="form--control">
                            <option value="percentage">{{ __('Percentage') }}</option>
                            <option value="flat">{{ __('Flat') }}</option>
                            <option value="bps">{{ __('Bps') }}</option>
                        </select>
                    </td>
                    <td><input type="number" step="0.00000001" name="tiers[${index}][fee_amount]" class="form--control" min="0"></td>
                    <td><input type="text" name="tiers[${index}][fee_currency]" class="form--control" placeholder="{{ __('Same as rule currency') }}"></td>
                    <td><input type="number" name="tiers[${index}][priority]" class="form--control" value="100" min="0"></td>
                    <td class="text-end"><button class="btn btn--danger btn--sm" type="button" data-remove-tier="true"><i class="las la-times"></i></button></td>
                </tr>
            `;
            tierWrapper.insertAdjacentHTML('beforeend', template);
        });

        tierWrapper.addEventListener('click', function(event){
            const target = event.target.closest('[data-remove-tier]');
            if (!target) {
                return;
            }
            const row = target.closest('tr');
            if (row && tierWrapper.children.length > 1) {
                row.remove();
            }
        });
    })();
</script>
@endpush
