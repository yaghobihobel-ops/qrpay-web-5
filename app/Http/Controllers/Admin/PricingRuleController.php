<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FeeTier;
use App\Models\PricingRule;
use App\Models\Pricing\PricingRule;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class PricingRuleController extends Controller
{
    public function index(): View
    {
        $page_title = __('Pricing Rules');
        $rules = PricingRule::with('feeTiers')->latest()->paginate(15);

        return view('admin.sections.pricing-rules.index', compact('page_title', 'rules'));
    }

    public function create(): View
    {
        $page_title = __('Create Pricing Rule');

        return view('admin.sections.pricing-rules.create', [
            'page_title' => $page_title,
            'rule' => new PricingRule(),
            'tiers' => collect([new FeeTier()]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $this->validateRequest($request);

        DB::transaction(function () use ($validated) {
            $tiers = $validated['tiers'];
            unset($validated['tiers']);

            $rule = PricingRule::create($validated);

            foreach ($tiers as $tier) {
                $rule->feeTiers()->create($tier);
            }
        });

        return redirect()->route('admin.pricing.rules.index')->with(['success' => [__('Pricing rule created successfully.')]]);
    }

    public function edit(PricingRule $rule): View
    {
        $page_title = __('Edit Pricing Rule');

        $rule->load('feeTiers');

        return view('admin.sections.pricing-rules.edit', [
            'page_title' => $page_title,
            'rule' => $rule,
            'tiers' => $rule->feeTiers->count() ? $rule->feeTiers : collect([new FeeTier()]),
        ]);
    }

    public function update(Request $request, PricingRule $rule): RedirectResponse
    {
        $validated = $this->validateRequest($request);

        DB::transaction(function () use ($rule, $validated) {
            $tiers = $validated['tiers'];
            unset($validated['tiers']);

            $rule->update($validated);

            $rule->feeTiers()->delete();
            foreach ($tiers as $tier) {
                $rule->feeTiers()->create($tier);
            }
        });

        return back()->with(['success' => [__('Pricing rule updated successfully.')]]);
    }

    public function destroy(PricingRule $rule): RedirectResponse
    {
        $rule->delete();

        return back()->with(['success' => [__('Pricing rule removed.')]]);
    }

    protected function validateRequest(Request $request): array
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'provider' => ['nullable', 'string', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'transaction_type' => ['required', 'string', 'max:255'],
            'user_level' => ['required', 'string', 'max:255'],
            'base_currency' => ['required', 'string', 'max:10'],
            'rate_provider' => ['nullable', 'string', 'max:255'],
            'spread_bps' => ['nullable', 'numeric', 'min:0'],
            'status' => ['required', 'boolean'],
            'conditions' => ['nullable', 'array'],
            'tiers' => ['required', 'array', 'min:1'],
            'tiers.*.min_amount' => ['required', 'numeric', 'min:0'],
            'tiers.*.max_amount' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.percent_fee' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.fixed_fee' => ['nullable', 'numeric', 'min:0'],
            'tiers.*.priority' => ['nullable', 'integer', 'min:0'],
        ]);

        $validated['spread_bps'] = $validated['spread_bps'] ?? 0;
        $validated['conditions'] = $validated['conditions'] ?? null;
        $validated['currency'] = $validated['currency'] ?: null;
        $validated['provider'] = $validated['provider'] ?: null;

        $validated['tiers'] = collect($validated['tiers'])->map(function (array $tier) {
            if (! empty($tier['max_amount']) && $tier['max_amount'] < $tier['min_amount']) {
                [$tier['min_amount'], $tier['max_amount']] = [$tier['max_amount'], $tier['min_amount']];
            }

            $tier['percent_fee'] = $tier['percent_fee'] ?? 0;
            $tier['fixed_fee'] = $tier['fixed_fee'] ?? 0;
            $tier['priority'] = $tier['priority'] ?? 0;

            return $tier;
        })->toArray();

        return $validated;
    }
}
